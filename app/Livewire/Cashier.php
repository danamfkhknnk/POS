<?php

namespace App\Livewire;

use App\Events\StockSynchronized;
use App\Models\Outlet;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.cashier')]
#[Title('Cashier')]
class Cashier extends Component
{
    /** @var list<array{id: int, name: string, sku: string, price: float, quantity: int}> */
    public array $cart = [];

    public string $search = '';

    public ?int $outletId = null;

    public ?string $outletName = null;

    public ?string $lastSaleSummary = null;

    public ?string $printTrxId = null;

    public function mount(): void
    {
        /** @var User&Authenticatable $user */
        $user = auth()->user();

        if (! $user || blank($user->outlet_id) || ! $user->hasRole('staff')) {
            $this->redirect(route('login'));

            return;
        }

        $this->outletId = $user->outlet_id;
        $this->outletName = $user->outlet?->name;
    }

    public function render(): mixed
    {
        return view('livewire.cashier', [
            'results' => $this->availableStocks(),
        ]);
    }

    /**
     * Products available to sell at the cashier's outlet, filtered by search term.
     */
    public function availableStocks(): mixed
    {
        return Stock::query()
            ->where('outlet_id', $this->outletId)
            ->where('is_active', true)
            ->with('product')
            ->whereHas('product', fn ($query) => $query
                ->where('is_active', true)
                ->when(trim($this->search) !== '', fn ($q) => $q->where(function ($inner) {
                    $term = '%'.trim($this->search).'%';

                    $inner->where('name', 'like', $term)->orWhere('sku', 'like', $term);
                })))
            ->orderBy('product_id')
            ->get();
    }

    public function addToCart(int $stockId): void
    {
        $stock = $this->findOwnOutletStock($stockId);

        if (! $stock) {
            return;
        }

        $lineIndex = $this->findLineIndex($stock->product_id);

        if ($lineIndex !== null) {
            if ($this->cart[$lineIndex]['quantity'] + 1 > $stock->quantity) {
                $this->addError('cart', "Only {$stock->quantity} unit(s) of {$stock->product->name} left in stock.");

                return;
            }

            $this->cart[$lineIndex]['quantity']++;
        } else {
            if ($stock->quantity < 1) {
                $this->addError('cart', "{$stock->product->name} is out of stock.");

                return;
            }

            $this->cart[] = [
                'id' => $stock->product_id,
                'name' => $stock->product->name,
                'sku' => $stock->product->sku,
                'price' => (float) $stock->product->price,
                'quantity' => 1,
            ];
        }

        $this->resetErrorBag();
    }

    public function incrementLine(int $productId): void
    {
        $lineIndex = $this->findLineIndex($productId);

        if ($lineIndex === null) {
            return;
        }

        $stock = $this->findOwnOutletStockByProduct($productId);

        if (! $stock || $this->cart[$lineIndex]['quantity'] + 1 > $stock->quantity) {
            $name = $this->cart[$lineIndex]['name'];

            $this->addError('cart', "Insufficient stock for {$name}.");

            return;
        }

        $this->cart[$lineIndex]['quantity']++;
        $this->clearCartError();
    }

    public function decrementLine(int $productId): void
    {
        $lineIndex = $this->findLineIndex($productId);

        if ($lineIndex === null) {
            return;
        }

        $this->cart[$lineIndex]['quantity']--;

        if ($this->cart[$lineIndex]['quantity'] < 1) {
            unset($this->cart[$lineIndex]);
            $this->cart = array_values($this->cart);
        }

        $this->clearCartError();
    }

    public function removeLine(int $productId): void
    {
        $this->cart = array_values(array_filter(
            $this->cart,
            fn (array $line): bool => $line['id'] !== $productId,
        ));

        $this->clearCartError();
    }

    public function updatedSearch(): void
    {
        $this->clearCartError();
    }

    public function syncCartOnStockChange(int $productId, int $newQuantity): void
    {
        $lineIndex = $this->findLineIndex($productId);

        if ($lineIndex === null) {
            return;
        }

        if ($newQuantity <= 0) {
            unset($this->cart[$lineIndex]);
            $this->cart = array_values($this->cart);
        } elseif ($this->cart[$lineIndex]['quantity'] > $newQuantity) {
            $this->cart[$lineIndex]['quantity'] = $newQuantity;
        }

        $this->clearCartError();
    }

    public function getCartTotalProperty(): float
    {
        return array_sum(array_map(
            fn (array $line): float => $line['price'] * $line['quantity'],
            $this->cart,
        ));
    }

    public function checkout(): void
    {
        if ($this->cart === []) {
            $this->addError('cart', 'The cart is empty.');

            return;
        }

        /** @var User&Authenticatable $cashier */
        $cashier = auth()->user();
        $cart = $this->cart;
        $trxId = Sale::generateTrxId();

        $result = DB::transaction(function () use ($cart, $cashier, $trxId): array {
            foreach ($cart as $line) {
                // Lock the stock row and re-validate against fresh data, so concurrent
                // sales from another cashier cannot drive the quantity below zero.
                $stock = Stock::query()
                    ->where('outlet_id', $this->outletId)
                    ->where('product_id', $line['id'])
                    ->lockForUpdate()
                    ->first();

                if (! $stock || $line['quantity'] > $stock->quantity) {
                    $available = $stock?->quantity ?? 0;

                    return ['ok' => false, 'message' => "Insufficient stock for {$line['name']} (only {$available} unit(s) left). The transaction was cancelled."];
                }

                $stock->decrement('quantity', $line['quantity']);

                event(new StockSynchronized(
                    outletId: (string) $this->outletId,
                    productId: (string) $line['id'],
                    quantity: $stock->quantity,
                ));

                $this->cashierOutlet()->sales()->create([
                    'trx_id' => $trxId,
                    'product_id' => $line['id'],
                    'user_id' => $cashier->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['price'],
                    'total' => $line['price'] * $line['quantity'],
                    'sold_at' => now(),
                ]);
            }

            return ['ok' => true, 'count' => count($cart)];
        });

        if (! $result['ok']) {
            $this->addError('cart', $result['message']);
            $this->syncCartWithStock();

            return;
        }

        $this->lastSaleSummary = sprintf(
            '%d item(s), total IDR %s',
            $result['count'],
            number_format($this->cartTotal, 0),
        );

        // Offer the receipt for printing after every completed checkout.
        $this->printTrxId = $trxId;

        $this->cart = [];
        $this->search = '';
        $this->clearCartError();

        $this->printReceipt($trxId);
    }

    /**
     * Print the receipt of a transaction via a dedicated print page loaded
     * into a hidden iframe, so only the receipt data is printed.
     */
    public function printReceipt(?string $trxId): void
    {
        if (blank($trxId)) {
            return;
        }

        $this->printTrxId = $trxId;

        $url = route('receipts.print', ['trxId' => $trxId]);

        $this->js(<<<JS
            window.setTimeout(() => {
                let frame = document.getElementById('receipt-print-frame');

                if (! frame) {
                    frame = document.createElement('iframe');
                    frame.id = 'receipt-print-frame';
                    frame.style.position = 'fixed';
                    frame.style.right = '0';
                    frame.style.bottom = '0';
                    frame.style.width = '0';
                    frame.style.height = '0';
                    frame.style.border = '0';
                    document.body.appendChild(frame);
                }

                frame.src = '{$url}';
            }, 50);
        JS);
    }

    /**
     * Drop cart lines that no longer exist or exceed the current outlet stock.
     */
    protected function syncCartWithStock(): void
    {
        $this->cart = array_values(array_filter(
            $this->cart,
            function (array $line): bool {
                $stock = $this->findOwnOutletStockByProduct($line['id']);

                return $stock !== null && $stock->quantity > 0;
            },
        ));

        foreach ($this->cart as $index => $line) {
            $stock = $this->findOwnOutletStockByProduct($line['id']);

            if ($stock && $line['quantity'] > $stock->quantity) {
                $this->cart[$index]['quantity'] = $stock->quantity;
            }
        }
    }

    protected function findOwnOutletStock(int $stockId): ?Stock
    {
        /** @var Stock|null $stock */
        $stock = Stock::query()
            ->where('outlet_id', $this->outletId)
            ->where('is_active', true)
            ->whereKey($stockId)
            ->with('product')
            ->first();

        return $stock !== null && $stock->product !== null && $stock->product->is_active ? $stock : null;
    }

    protected function findOwnOutletStockByProduct(int $productId): ?Stock
    {
        /** @var Stock|null $stock */
        $stock = Stock::query()
            ->where('outlet_id', $this->outletId)
            ->where('is_active', true)
            ->where('product_id', $productId)
            ->with('product')
            ->first();

        return $stock !== null && $stock->product !== null && $stock->product->is_active ? $stock : null;
    }

    protected function findLineIndex(int $productId): ?int
    {
        foreach ($this->cart as $index => $line) {
            if ($line['id'] === $productId) {
                return $index;
            }
        }

        return null;
    }

    protected function clearCartError(): void
    {
        if ($this->getErrorBag()->has('cart')) {
            $this->resetErrorBag('cart');
        }
    }

    protected function cashierOutlet(): ?Outlet
    {
        return $this->outletId === null ? null : Outlet::find($this->outletId);
    }
}

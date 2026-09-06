<?php

namespace App\Livewire\Cashier;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.cashier')]
#[Title('Transactions')]
class Transactions extends Component
{
    public string $search = '';

    public ?string $dateFrom = null;

    public ?string $dateUntil = null;

    public ?string $detailTrxId = null;

    public function mount(): void
    {
        /** @var User&Authenticatable $user */
        $user = auth()->user();

        if (! $user || blank($user->outlet_id) || ! $user->hasRole('staff')) {
            $this->redirect(route('login'));

            return;
        }
    }

    public function render(): mixed
    {
        return view('livewire.cashier.transactions', [
            'transactions' => $this->transactions(),
        ]);
    }

    /**
     * Transactions (grouped by trx_id) recorded at the cashier's outlet.
     */
    public function transactions(): mixed
    {
        $term = trim($this->search);

        return Sale::query()
            ->selectRaw('trx_id, MIN(sold_at) as sold_at, COUNT(DISTINCT product_id) as items, SUM(quantity) as units, SUM(total) as total, MIN(user_id) as user_id, outlet_id')
            ->where('outlet_id', auth()->user()->outlet_id)
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($inner) use ($term) {
                    $like = '%'.$term.'%';

                    $inner
                        ->where('trx_id', 'like', $like)
                        ->orWhereHas('product', fn ($q) => $q->where('name', 'like', $like)->orWhere('sku', 'like', $like));
                });
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('sold_at', '>=', $this->dateFrom))
            ->when($this->dateUntil, fn ($q) => $q->whereDate('sold_at', '<=', $this->dateUntil))
            ->groupBy('trx_id', 'outlet_id')
            ->orderByDesc('sold_at')
            ->get();
    }

    public function openDetail(string $trxId): void
    {
        $this->detailTrxId = $trxId;
    }

    public function closeDetail(): void
    {
        $this->detailTrxId = null;
    }

    public function printReceipt(string $trxId): void
    {
        $this->detailTrxId = $trxId;

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
     * The transaction currently opened in the detail modal (or requested for print).
     */
    public function getDetailTrxProperty(): ?Sale
    {
        if ($this->detailTrxId === null) {
            return null;
        }

        return Sale::query()
            ->where('outlet_id', auth()->user()->outlet_id)
            ->where('trx_id', $this->detailTrxId)
            ->with(['outlet', 'user', 'lines.product'])
            ->first();
    }

    public function getReceiptUrlProperty(): ?string
    {
        return $this->detailTrx === null
            ? null
            : route('receipts.print', ['trxId' => $this->detailTrx->trx_id]);
    }
}

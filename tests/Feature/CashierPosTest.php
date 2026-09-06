<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Cashier;
use App\Livewire\Cashier\Transactions;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashierPosTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('cashier.index'))->assertRedirect(route('login'));
    }

    public function test_login_redirects_staff_to_cashier_interface(): void
    {
        $staff = $this->staffUser();

        Livewire::test(Login::class)
            ->set('email', $staff->email)
            ->set('password', 'password')
            ->call('authenticate')
            ->assertRedirect($staff->getHomeUrl());

        $this->assertAuthenticatedAs($staff);
    }

    public function test_staff_cannot_access_filament_panel(): void
    {
        $staff = $this->staffUser();

        $this->actingAs($staff)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_access_filament_panel(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(config('filament.path', 'admin'))
            ->assertSuccessful(); // The Filament dashboard renders for admins.
    }

    public function test_cart_total_and_lines_are_calculated_dynamically(): void
    {
        $staff = $this->staffUser();
        $stock = $this->stockFor($staff, 10, 15000);

        $component = Livewire::actingAs($staff)->test(Cashier::class)
            ->call('addToCart', $stock->id)
            ->call('incrementLine', $stock->product_id);

        $this->assertSame(2, $component->get('cart.0.quantity'));
        $this->assertSame(30000.0, $component->get('cartTotal'));
    }

    public function test_checkout_decrements_stock_and_records_sales(): void
    {
        $staff = $this->staffUser();
        $stockA = $this->stockFor($staff, 10, 15000);
        $stockB = $this->stockFor($staff, 5, 3000);

        Livewire::actingAs($staff)->test(Cashier::class)
            ->call('addToCart', $stockA->id)
            ->call('incrementLine', $stockA->product_id) // 2 × 15.000
            ->call('addToCart', $stockB->id)             // 1 × 3.000
            ->call('checkout');

        $this->assertSame(8, $stockA->refresh()->quantity);
        $this->assertSame(4, $stockB->refresh()->quantity);

        $this->assertSame(2, Sale::query()->count());

        $saleA = Sale::query()->where('product_id', $stockA->product_id)->sole();
        $this->assertSame($staff->outlet->id, $saleA->outlet_id);
        $this->assertSame($staff->id, $saleA->user_id);
        $this->assertSame(2, $saleA->quantity);
        $this->assertSame('15000.00', (string) $saleA->unit_price);
        $this->assertSame('30000.00', (string) $saleA->total);
        $this->assertTrue($saleA->sold_at->isToday());
    }

    public function test_checkout_assigns_one_shared_trx_id_to_all_lines(): void
    {
        $staff = $this->staffUser();
        $stockA = $this->stockFor($staff, 10, 15000);
        $stockB = $this->stockFor($staff, 5, 3000);

        $component = Livewire::actingAs($staff)->test(Cashier::class)
            ->call('addToCart', $stockA->id)
            ->call('addToCart', $stockB->id)
            ->call('checkout');

        $trxIds = Sale::query()->pluck('trx_id')->unique();

        $this->assertSame(1, $trxIds->count());
        $this->assertNotNull($trxIds->first());
        $this->assertStringStartsWith('TRX-', $trxIds->first());
        $this->assertSame($trxIds->first(), $component->get('printTrxId'));
    }

    public function test_print_button_is_offered_after_successful_checkout(): void
    {
        $staff = $this->staffUser();
        $stock = $this->stockFor($staff, 10, 15000);

        $component = Livewire::actingAs($staff)->test(Cashier::class)
            ->call('addToCart', $stock->id);

        $this->assertNull($component->get('printTrxId'));

        $component->call('checkout')
            ->assertSee('Print Receipt');

        $this->assertNotNull($component->get('printTrxId'));

        // The printable receipt partial is rendered with the transaction data.
        $trxId = $component->get('printTrxId');
        $component->assertSee($trxId);
    }

    public function test_cart_cannot_exceed_available_stock(): void
    {
        $staff = $this->staffUser();
        $stock = $this->stockFor($staff, 3, 10000);

        // Cart holds a valid line, then a third increment hits the stock limit.
        $component = Livewire::actingAs($staff)->test(Cashier::class)
            ->call('addToCart', $stock->id)
            ->call('incrementLine', $stock->product_id)
            ->call('incrementLine', $stock->product_id)
            ->call('incrementLine', $stock->product_id);

        $this->assertTrue($component->errors()->has('cart'));
        $this->assertSame(3, $component->get('cart.0.quantity'));

        // Correcting the quantity clears the error; no stock or sale was touched.
        $component->call('decrementLine', $stock->product_id);

        $this->assertFalse($component->errors()->has('cart'));
        $this->assertSame(3, $stock->refresh()->quantity);
        $this->assertSame(0, Sale::query()->count());
    }

    public function test_checkout_rejects_stale_cart_when_stock_changed_concurrently(): void
    {
        $staff = $this->staffUser();
        $stock = $this->stockFor($staff, 5, 10000);

        $component = Livewire::actingAs($staff)->test(Cashier::class)
            ->call('addToCart', $stock->id)
            ->call('incrementLine', $stock->product_id)
            ->call('incrementLine', $stock->product_id); // cart wants 3

        // Another cashier sells 4 units while this cart is open → only 1 left.
        $stock->decrement('quantity', 4);

        $component->call('checkout');

        $this->assertTrue($component->errors()->has('cart'));
        $this->assertSame(1, $stock->refresh()->quantity);
        $this->assertSame(0, Sale::query()->count());
        $this->assertSame(1, $component->get('cart.0.quantity')); // cart synced to available stock
    }

    public function test_sale_snapshots_the_current_product_price(): void
    {
        $staff = $this->staffUser();
        $stock = $this->stockFor($staff, 10, 20000);

        Livewire::actingAs($staff)->test(Cashier::class)
            ->call('addToCart', $stock->id)
            ->call('checkout');

        $sale = Sale::query()->sole();
        $this->assertSame('20000.00', (string) $sale->unit_price);

        // Later price changes must not affect the historical record.
        $stock->product->update(['price' => 999999]);

        $this->assertSame('20000.00', $sale->refresh()->unit_price);
    }

    public function test_search_filters_products_by_name_and_sku(): void
    {
        $staff = $this->staffUser();
        $mouse = Product::factory()->create(['name' => 'Wireless Mouse', 'sku' => 'ELC-001']);
        $book = Product::factory()->create(['name' => 'Notebook', 'sku' => 'STN-009']);

        Stock::factory()->for($mouse)->for($staff->outlet)->create(['quantity' => 5]);
        Stock::factory()->for($book)->for($staff->outlet)->create(['quantity' => 5]);

        Livewire::actingAs($staff)->test(Cashier::class)
            ->set('search', 'mouse')
            ->assertSee('Wireless Mouse')
            ->assertDontSee('Notebook')
            ->set('search', 'STN')
            ->assertSee('Notebook')
            ->assertDontSee('Wireless Mouse');
    }

    public function test_inactive_stock_is_not_sellable(): void
    {
        $staff = $this->staffUser();
        $product = Product::factory()->create(['name' => 'Old Product']);
        Stock::factory()->for($product)->for($staff->outlet)->create(['quantity' => 5, 'is_active' => false]);

        Livewire::actingAs($staff)->test(Cashier::class)
            ->assertDontSee('Old Product');
    }

    public function test_cannot_add_product_from_another_outlet(): void
    {
        $staff = $this->staffUser();
        $otherOutletStock = Stock::factory()
            ->for(Product::factory())
            ->for(Outlet::factory()->create())
            ->create(['quantity' => 10]);

        Livewire::actingAs($staff)->test(Cashier::class)
            ->call('addToCart', $otherOutletStock->id);

        $this->assertSame([], Livewire::actingAs($staff)->test(Cashier::class)->get('cart'));
    }

    public function test_staff_transactions_page_lists_grouped_transactions(): void
    {
        $staff = $this->staffUser();
        $stockA = $this->stockFor($staff, 10, 15000);
        $stockB = $this->stockFor($staff, 5, 3000);

        // One checkout with two lines → one transaction row.
        Livewire::actingAs($staff)->test(Cashier::class)
            ->call('addToCart', $stockA->id)
            ->call('addToCart', $stockB->id)
            ->call('checkout');

        Livewire::actingAs($staff)->test(Transactions::class)
            ->assertSuccessful()
            ->assertSee('TRX-')
            ->assertSee('IDR 18,000'); // 1 × 15,000 + 1 × 3,000

        $this->assertSame(1, Sale::query()->distinct('trx_id')->count('trx_id'));
    }

    public function test_staff_transactions_search_filters_by_trx_id_and_product(): void
    {
        $staff = $this->staffUser();
        $stock = $this->stockFor($staff, 10, 15000);
        $otherStock = $this->stockFor($staff, 5, 5000);

        Livewire::actingAs($staff)->test(Cashier::class)
            ->call('addToCart', $stock->id)
            ->call('checkout');

        $trxId = Sale::query()->sole()->trx_id;

        Livewire::actingAs($staff)->test(Cashier::class)
            ->call('addToCart', $otherStock->id)
            ->call('checkout');

        $this->assertSame(2, Sale::query()->distinct('trx_id')->count('trx_id'));

        Livewire::actingAs($staff)->test(Transactions::class)
            ->set('search', $trxId)
            ->assertSee($trxId);

        $foundCount = Livewire::actingAs($staff)->test(Transactions::class)
            ->set('search', 'Wireless')
            ->instance()
            ->transactions()
            ->count();

        $this->assertSame(0, $foundCount); // No product named Wireless here.
    }

    public function test_staff_transaction_detail_and_print(): void
    {
        $staff = $this->staffUser();
        $stock = $this->stockFor($staff, 10, 15000);

        $component = Livewire::actingAs($staff)->test(Cashier::class)
            ->call('addToCart', $stock->id)
            ->call('checkout');

        $trxId = $component->get('printTrxId');

        $page = Livewire::actingAs($staff)->test(Transactions::class)
            ->call('openDetail', $trxId);

        $page->assertSee('Transaction Detail')
            ->assertSee($trxId)
            ->assertSee('Print Receipt');

        $this->assertSame($trxId, $page->get('detailTrxId'));

        $page->call('closeDetail');
        $this->assertNull($page->get('detailTrxId'));

        // Printing from the list opens the receipt for the right transaction.
        Livewire::actingAs($staff)->test(Transactions::class)
            ->call('printReceipt', $trxId)
            ->assertSee($trxId);
    }

    public function test_custom_receipt_print_page_renders_only_transaction_data(): void
    {
        $staff = $this->staffUser();
        $stockA = $this->stockFor($staff, 10, 15000);
        $stockB = $this->stockFor($staff, 5, 3000);

        Livewire::actingAs($staff)->test(Cashier::class)
            ->call('addToCart', $stockA->id)
            ->call('addToCart', $stockB->id)
            ->call('checkout');

        $trxId = Sale::query()->first()->trx_id;

        $response = $this->actingAs($staff)->get(route('receipts.print', ['trxId' => $trxId]));

        $response->assertSuccessful();
        $response->assertSee($trxId);
        $response->assertSee($stockA->product->name);
        $response->assertSee($stockB->product->name);
        $response->assertSee('IDR 18,000');
        $response->assertSee('Cashier');
        $response->assertSee('TOTAL');

        // It is a dedicated print page, not the app layout.
        $response->assertDontSee('wire:model');
        $response->assertDontSee('Sign out');
    }

    public function test_guests_cannot_access_receipt_print_page(): void
    {
        $this->get(route('receipts.print', ['trxId' => 'TRX-260906-DEADBEEF']))
            ->assertRedirect(route('login'));
    }

    public function test_staff_cannot_print_other_outlet_receipt(): void
    {
        $staff = $this->staffUser();
        $otherOutlet = Outlet::factory()->create();
        $otherStaff = User::factory()->for($otherOutlet, 'outlet')->hasAttached(Role::firstOrCreate(['name' => 'staff']))->create();

        $sale = Sale::factory()->for(Product::factory())->for($otherOutlet)->for($otherStaff, 'user')->create();

        $this->actingAs($staff)
            ->get(route('receipts.print', ['trxId' => $sale->trx_id]))
            ->assertForbidden();
    }

    public function test_staff_cannot_see_other_outlet_transactions(): void
    {
        $staff = $this->staffUser();
        $otherOutlet = Outlet::factory()->create();
        $otherProduct = Product::factory()->create(['name' => 'Other Outlet Product']);

        Sale::factory()->for($otherProduct)->for($otherOutlet)->for(User::factory()->for($otherOutlet, 'outlet')->hasAttached(Role::firstOrCreate(['name' => 'staff'])), 'user')->create();

        Livewire::actingAs($staff)->test(Transactions::class)
            ->assertDontSee('Other Outlet Product');

        $this->assertSame(0, Livewire::actingAs($staff)->test(Transactions::class)->instance()->transactions()->count());
    }

    private function staffUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'staff'], ['description' => 'Staff Outlet']);
        $outlet = Outlet::factory()->create();

        return User::factory()->for($outlet, 'outlet')->hasAttached($role)->create();
    }

    private function adminUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin Pusat']);

        return User::factory()->hasAttached($role)->create(['outlet_id' => null]);
    }

    private function stockFor(User $staff, int $quantity, int $price): Stock
    {
        $product = Product::factory()->create(['price' => $price]);

        return Stock::factory()->for($product)->for($staff->outlet)->create(['quantity' => $quantity]);
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Pages\Inventory;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');

        $this->actingAs(User::factory()->create());
    }

    public function test_renders_stock_rows_for_all_product_outlet_combinations(): void
    {
        $product = Product::factory()->create();
        $outlet = Outlet::factory()->create();
        Stock::factory()->for($product)->for($outlet)->create(['quantity' => 16, 'min_stock' => 10]);

        Livewire::test(Inventory::class)
            ->assertSuccessful()
            ->assertSee($product->name)
            ->assertSee($outlet->name);
    }

    public function test_individual_update_action_updates_all_stock_fields(): void
    {
        $stock = Stock::factory()
            ->for(Product::factory())
            ->for(Outlet::factory())
            ->create(['quantity' => 5, 'min_stock' => 2, 'is_active' => true]);

        Livewire::test(Inventory::class)
            ->mountTableAction('updateStock', $stock)
            ->set('mountedActions.0.data.quantity', 40)
            ->set('mountedActions.0.data.min_stock', 8)
            ->set('mountedActions.0.data.is_active', false)
            ->callMountedTableAction();

        $this->assertSame(40, $stock->refresh()->quantity);
        $this->assertSame(8, $stock->min_stock);
        $this->assertFalse($stock->is_active);
    }

    public function test_bulk_update_modal_lists_product_outlet_rows(): void
    {
        $product = Product::factory()->create(['name' => 'USB-C Cable']);
        $outlet = Outlet::factory()->create(['name' => 'Jakarta']);
        $stock = Stock::factory()->for($product)->for($outlet)->create();

        Livewire::test(Inventory::class)
            ->mountTableBulkAction('bulkUpdate', [$stock])
            ->assertSee('USB-C Cable - Jakarta');
    }

    public function test_bulk_update_updates_only_selected_rows(): void
    {
        $product = Product::factory()->create();
        $jakarta = Outlet::factory()->create();
        $bandung = Outlet::factory()->create();
        $surabaya = Outlet::factory()->create();

        $jakartaStock = Stock::factory()->for($product)->for($jakarta)->create(['quantity' => 4]);
        $bandungStock = Stock::factory()->for($product)->for($bandung)->create(['quantity' => 6]);
        $surabayaStock = Stock::factory()->for($product)->for($surabaya)->create(['quantity' => 8]);

        Livewire::test(Inventory::class)
            ->mountTableBulkAction('bulkUpdate', [$jakartaStock, $bandungStock, $surabayaStock])
            ->set('mountedActions.0.data.rows', [$jakartaStock->getKey(), $bandungStock->getKey()])
            ->set('mountedActions.0.data.operation', 'set')
            ->set('mountedActions.0.data.value', 10)
            ->callMountedTableBulkAction();

        $this->assertSame(10, $jakartaStock->refresh()->quantity);
        $this->assertSame(10, $bandungStock->refresh()->quantity);
        $this->assertSame(8, $surabayaStock->refresh()->quantity);
    }

    public function test_bulk_update_adds_and_subtracts_without_going_below_zero(): void
    {
        $product = Product::factory()->create();
        $outlet = Outlet::factory()->create();

        $stock = Stock::factory()->for($product)->for($outlet)->create(['quantity' => 4]);

        Livewire::test(Inventory::class)
            ->mountTableBulkAction('bulkUpdate', [$stock])
            ->set('mountedActions.0.data.rows', [$stock->getKey()])
            ->set('mountedActions.0.data.operation', 'add')
            ->set('mountedActions.0.data.value', 6)
            ->callMountedTableBulkAction();

        $this->assertSame(10, $stock->refresh()->quantity);

        Livewire::test(Inventory::class)
            ->mountTableBulkAction('bulkUpdate', [$stock])
            ->set('mountedActions.0.data.rows', [$stock->getKey()])
            ->set('mountedActions.0.data.operation', 'subtract')
            ->set('mountedActions.0.data.value', 50)
            ->callMountedTableBulkAction();

        $this->assertSame(0, $stock->refresh()->quantity);
    }

    public function test_bulk_update_skips_deselected_rows(): void
    {
        $product = Product::factory()->create();
        $outletA = Outlet::factory()->create();
        $outletB = Outlet::factory()->create();

        $stockA = Stock::factory()->for($product)->for($outletA)->create(['quantity' => 5]);
        $stockB = Stock::factory()->for($product)->for($outletB)->create(['quantity' => 7]);

        Livewire::test(Inventory::class)
            ->mountTableBulkAction('bulkUpdate', [$stockA, $stockB])
            ->set('mountedActions.0.data.rows', [$stockA->getKey()])
            ->set('mountedActions.0.data.operation', 'set')
            ->set('mountedActions.0.data.value', 12)
            ->callMountedTableBulkAction();

        $this->assertSame(12, $stockA->refresh()->quantity);
        $this->assertSame(7, $stockB->refresh()->quantity);
    }
}

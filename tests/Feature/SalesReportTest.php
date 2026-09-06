<?php

namespace Tests\Feature;

use App\Filament\Resources\Sales\Pages\ListSales;
use App\Filament\Resources\Sales\Pages\ViewSale;
use App\Filament\Resources\Sales\SaleResource;
use App\Filament\Widgets\SalesChartWidget;
use App\Filament\Widgets\SalesStatsWidget;
use App\Filament\Widgets\TopProductsWidget;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');

        $this->actingAs($this->adminUser());
    }

    public function test_transactions_page_lists_sales_with_details(): void
    {
        $sale = $this->createSale([
            'quantity' => 3,
            'unit_price' => 15000,
            'total' => 45000,
        ]);

        Livewire::test(ListSales::class)
            ->assertSuccessful()
            ->assertSee($sale->trx_id)
            ->assertSee($sale->product->name)
            ->assertSee($sale->outlet->name)
            ->assertSee($sale->user->name);
    }

    public function test_transaction_detail_page_shows_full_record(): void
    {
        $sale = $this->createSale(['quantity' => 2, 'unit_price' => 5000, 'total' => 10000]);

        Livewire::test(ViewSale::class, ['record' => $sale->getKey()])
            ->assertSuccessful()
            ->assertSee($sale->trx_id)
            ->assertSee($sale->product->name)
            ->assertSee($sale->user->name);
    }

    public function test_transactions_are_read_only(): void
    {
        $sale = $this->createSale();

        $this->assertFalse(SaleResource::canCreate());
        $this->assertFalse(SaleResource::canEdit($sale));
        $this->assertFalse(SaleResource::canDelete($sale));
        $this->assertFalse(SaleResource::canDeleteAny());

        // No create/edit/delete pages exist at all.
        $this->assertSame(['index', 'view'], array_keys(SaleResource::getPages()));
    }

    public function test_transactions_can_be_filtered_by_outlet(): void
    {
        $jakartaSale = $this->createSale(['outlet_name' => 'Jakarta']);
        $bandungSale = $this->createSale(['outlet_name' => 'Bandung']);

        Livewire::test(ListSales::class)
            ->set('tableFilters.outlet.value', $jakartaSale->outlet_id)
            ->assertSee($jakartaSale->product->name)
            ->assertDontSee($bandungSale->product->name);
    }

    public function test_stats_widget_shows_sales_metrics(): void
    {
        $this->createSale(['quantity' => 2, 'unit_price' => 10000, 'total' => 20000]);
        $this->createSale(['quantity' => 1, 'unit_price' => 5000, 'total' => 5000]);

        Livewire::test(SalesStatsWidget::class)
            ->assertSuccessful()
            ->assertSee('Rp 25.000')   // Revenue today
            ->assertSee('2')           // Transactions today
            ->assertSee('3 units sold');
    }

    public function test_chart_widget_renders_with_daily_revenue(): void
    {
        $this->createSale(['total' => 120000]);

        $widget = Livewire::test(SalesChartWidget::class);

        $widget->assertSuccessful();

        $getData = new \ReflectionMethod(SalesChartWidget::class, 'getData');
        $data = $getData->invoke($widget->instance());

        $getType = new \ReflectionMethod(SalesChartWidget::class, 'getType');

        $this->assertSame('line', $getType->invoke($widget->instance()));
        $this->assertCount(14, $data['labels']);
        $this->assertCount(14, $data['datasets'][0]['data']);
        $this->assertContains(120000.0, $data['datasets'][0]['data']);
    }

    public function test_top_products_widget_ranks_best_sellers(): void
    {
        $topSale = $this->createSale(['product_name' => 'Wireless Mouse', 'quantity' => 8, 'unit_price' => 15000, 'total' => 120000]);
        $lowSale = $this->createSale(['product_name' => 'Ballpoint Pen', 'quantity' => 1, 'unit_price' => 3000, 'total' => 3000]);

        $table = Livewire::test(TopProductsWidget::class);

        $table->assertSuccessful()
            ->assertSee('Wireless Mouse')
            ->assertSee('Ballpoint Pen');

        $rows = $table->instance()->getTableRecords();
        $this->assertTrue($rows->contains('name', 'Wireless Mouse'));
        $this->assertSame(8, (int) $rows->firstWhere('name', 'Wireless Mouse')->units_sold);

        // Best seller first
        $this->assertSame('Wireless Mouse', $rows->first()->name);
        $this->assertTrue($lowSale->product->name === 'Ballpoint Pen');
    }

    public function test_dashboard_widgets_are_registered(): void
    {
        $widgets = Filament::getCurrentOrDefaultPanel()->getWidgets();

        $this->assertContains(SalesStatsWidget::class, $widgets);
        $this->assertContains(SalesChartWidget::class, $widgets);
        $this->assertContains(TopProductsWidget::class, $widgets);
    }

    private function createSale(array $attributes = []): Sale
    {
        $outlet = Outlet::factory()->create(['name' => $attributes['outlet_name'] ?? 'Outlet '.fake()->unique()->city()]);
        $product = Product::factory()->create(['name' => $attributes['product_name'] ?? 'Product '.fake()->unique()->words(2, true)]);
        $cashier = User::factory()->for($outlet, 'outlet')->hasAttached(Role::firstOrCreate(['name' => 'staff']))->create();

        return Sale::factory()->for($product)->for($outlet)->for($cashier, 'user')->create([
            'quantity' => $attributes['quantity'] ?? 1,
            'unit_price' => $attributes['unit_price'] ?? $product->price,
            'total' => $attributes['total'] ?? $product->price,
            'sold_at' => $attributes['sold_at'] ?? now(),
        ]);
    }

    private function adminUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin Pusat']);

        return User::factory()->hasAttached($role)->create(['outlet_id' => null]);
    }
}

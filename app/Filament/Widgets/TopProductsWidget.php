<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopProductsWidget extends TableWidget
{
    use InteractsWithTable;

    protected static ?int $sort = 2;

    protected static ?string $heading = 'Best Selling Products';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->select('products.*')
                    ->selectRaw('COALESCE(SUM(sales.quantity), 0) as units_sold, COALESCE(SUM(sales.total), 0) as revenue')
                    ->leftJoin('sales', 'sales.product_id', '=', 'products.id')
                    ->groupBy('products.id')
                    ->having('units_sold', '>', 0)
                    ->orderByDesc('units_sold')
                    ->limit(5),
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Product'),
                TextColumn::make('sku')
                    ->label('SKU'),
                TextColumn::make('units_sold')
                    ->label('Units Sold'),
                TextColumn::make('revenue')
                    ->money('IDR'),
            ])
            ->paginated(false);
    }
}

<?php

namespace App\Filament\Resources\Sales\Tables;

use App\Models\Outlet;
use App\Models\Sale;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(Sale::query()->with(['product', 'outlet', 'user']))
            ->defaultSort('sold_at', 'desc')
            ->columns([
                TextColumn::make('trx_id')
                    ->label('Trx ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono'),
                TextColumn::make('sold_at')
                    ->label('Time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('outlet.name')
                    ->label('Outlet')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')
                    ->label('Cashier')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('total')
                    ->money('IDR')
                    ->weight(FontWeight::Bold)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('outlet')
                    ->label('Outlet')
                    ->options(Outlet::query()->orderBy('name')->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $state) => $query->when(
                        filled($state['value'] ?? null),
                        fn (Builder $query) => $query->where('outlet_id', $state['value']),
                    )),
                Filter::make('sold_at')
                    ->label('Date range')
                    ->schema([
                        DatePicker::make('sold_from')
                            ->label('From'),
                        DatePicker::make('sold_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['sold_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('sold_at', '>=', $date),
                            )
                            ->when(
                                $data['sold_until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('sold_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}

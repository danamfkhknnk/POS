<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    TextColumn::make('name')
                        ->weight(FontWeight::Bold)
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('sku')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('category.name')
                        ->label('Category')
                        ->sortable(),
                    TextColumn::make('price')
                        ->money('IDR')
                        ->sortable(),
                ])->from('md'),
                Panel::make([
                    Stack::make([
                        View::make('filament.products.stock-row'),
                    ]),
                ])->collapsible()->collapsed(),
            ])
            ->recordAction(null)
            ->modifyQueryUsing(fn ($query) => $query->with('outlets'))
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(Category::pluck('name', 'id')),
            ])
            ->recordActions([
                Action::make('editProduct')
                    ->label('Edit Product')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn ($record) => ProductResource::getUrl('edit', ['record' => $record->id])),
                DeleteAction::make(),
            ]);
    }
}

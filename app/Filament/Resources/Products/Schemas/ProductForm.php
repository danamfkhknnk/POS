<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    TextInput::make('name')
                        ->label('Product Name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('sku')
                        ->label('SKU')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                ]),
                Grid::make(2)->schema([
                    Select::make('category_id')
                        ->label('Category')
                        ->options(Category::pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                    TextInput::make('price')
                        ->label('Price')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->default(0),
                ]),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}

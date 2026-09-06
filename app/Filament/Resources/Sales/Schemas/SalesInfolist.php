<?php

namespace App\Filament\Resources\Sales\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class SalesInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('trx_id')
                    ->label('Trx ID')
                    ->copyable()
                    ->fontFamily('mono')
                    ->weight(FontWeight::Bold),
                TextEntry::make('outlet.name')
                    ->label('Outlet'),
                TextEntry::make('product.name')
                    ->label('Product'),
                TextEntry::make('product.sku')
                    ->label('SKU'),
                TextEntry::make('user.name')
                    ->label('Cashier'),
                TextEntry::make('quantity')
                    ->numeric(),
                TextEntry::make('unit_price')
                    ->label('Unit Price')
                    ->money('IDR'),
                TextEntry::make('total')
                    ->money('IDR')
                    ->weight(FontWeight::Bold),
                TextEntry::make('sold_at')
                    ->label('Sold at')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->label('Recorded at')
                    ->dateTime()
                    ->helperText('When the transaction entered the system.'),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable()->sortable(),
                TextColumn::make('description')->limit(50)->wrap(),
                TextColumn::make('products_count')->counts('products')->label('Products')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, Category $record): void {
                        if (! $record->products()->exists()) {
                            return;
                        }

                        Notification::make()
                            ->title('Category still has products')
                            ->body("Move or delete the {$record->products()->count()} product(s) in this category first.")
                            ->danger()
                            ->send();

                        $action->halt();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (DeleteBulkAction $action, EloquentCollection $records): void {
                            $withProducts = Category::query()
                                ->whereKey($records->pluck('id'))
                                ->has('products')
                                ->count();

                            if ($withProducts === 0) {
                                return;
                            }

                            Notification::make()
                                ->title('Some categories still have products')
                                ->body("Move or delete the products in those {$withProducts} categorie(s) first.")
                                ->danger()
                                ->send();

                            $action->halt();
                        }),
                ]),
            ]);
    }
}

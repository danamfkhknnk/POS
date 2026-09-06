<?php

namespace App\Filament\Pages;

use App\Events\StockSynchronized;
use App\Models\Category;
use App\Models\Stock;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class Inventory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $navigationLabel = 'Inventory';

    protected static string|UnitEnum|null $navigationGroup = 'Products';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Inventory Management';

    protected string $view = 'filament.pages.inventory';

    public function table(Table $table): Table
    {
        return $table
            ->query(Stock::query()->with(['product.category', 'outlet']))
            ->defaultSort('product.name')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('outlet.name')
                    ->label('Outlet')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Stock')
                    ->sortable(),
                TextColumn::make('min_stock')
                    ->label('Min Stock')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                TextColumn::make('product.category.name')
                    ->label('Category')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('outlet')
                    ->label('Outlet')
                    ->relationship('outlet', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('category')
                    ->label('Category')
                    ->options(Category::pluck('name', 'id'))
                    ->query(fn (Builder $query, array $state) => $query
                        ->when(
                            filled($state['value'] ?? null),
                            fn (Builder $query) => $query->whereRelation('product', 'category_id', $state['value']),
                        )),
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                Action::make('updateStock')
                    ->label('Update')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->form(fn (Stock $record) => [
                        TextInput::make('quantity')
                            ->label('Stock')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default($record->quantity),
                        TextInput::make('min_stock')
                            ->label('Min Stock')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default($record->min_stock),
                        Select::make('is_active')
                            ->label('Active')
                            ->boolean()
                            ->required()
                            ->default($record->is_active),
                    ])
                    ->action(function (Stock $record, array $data): void {
                        $record->update($data);

                        event(new StockSynchronized(
                            outletId: (string) $record->outlet_id,
                            productId: (string) $record->product_id,
                            quantity: $record->quantity,
                        ));

                        Notification::make()
                            ->title('Stock updated')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkUpdate')
                        ->label('Bulk Update Stock')
                        ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                        ->form(fn (Collection $records) => [
                            Select::make('rows')
                                ->label('Rows to update')
                                ->options($records->mapWithKeys(fn (Stock $record): array => [
                                    $record->getKey() => "{$record->product->name} - {$record->outlet->name}",
                                ]))
                                ->multiple()
                                ->searchable()
                                ->default($records->modelKeys())
                                ->helperText('Deselect the rows you do not want to update.')
                                ->required(),
                            Select::make('operation')
                                ->label('Operation')
                                ->options([
                                    'set' => 'Set',
                                    'add' => 'Add',
                                    'subtract' => 'Subtract',
                                ])
                                ->default('set')
                                ->required(),
                            TextInput::make('value')
                                ->label('Value')
                                ->numeric()
                                ->integer()
                                ->required()
                                ->minValue(0),
                        ])
                        ->modalSubmitActionLabel('Update Stock')
                        ->action(function (Collection $records, array $data): void {
                            $rowIds = array_map(intval(...), (array) $data['rows']);
                            $value = (int) $data['value'];

                            foreach ($records as $record) {
                                if (! in_array($record->getKey(), $rowIds, true)) {
                                    continue;
                                }

                                $quantity = match ($data['operation']) {
                                    'set' => $value,
                                    'add' => $record->quantity + $value,
                                    'subtract' => max(0, $record->quantity - $value),
                                };

                                if ($quantity === $record->quantity) {
                                    continue;
                                }

                                $record->update(['quantity' => $quantity]);

                                event(new StockSynchronized(
                                    outletId: (string) $record->outlet_id,
                                    productId: (string) $record->product_id,
                                    quantity: $quantity,
                                ));
                            }

                            Notification::make()
                                ->title('Stock updated successfully')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

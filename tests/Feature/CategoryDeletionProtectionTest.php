<?php

namespace Tests\Feature;

use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryDeletionProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');

        $this->actingAs(User::factory()->create());
    }

    public function test_deletes_category_without_products(): void
    {
        $category = Category::factory()->create();

        Livewire::test(ListCategories::class)
            ->callTableAction('delete', $category);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_blocks_deleting_category_with_products_and_notifies(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(2)->for($category)->create();

        Livewire::test(ListCategories::class)
            ->callTableAction('delete', $category)
            ->assertActionMounted()
            ->assertNotified('Category still has products');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertSame(2, Product::query()->where('category_id', $category->id)->count());
    }

    public function test_blocks_bulk_deleting_categories_with_products_and_notifies(): void
    {
        $emptyCategory = Category::factory()->create();
        $filledCategory = Category::factory()->create();
        Product::factory()->for($filledCategory)->create();

        Livewire::test(ListCategories::class)
            ->callTableBulkAction('delete', [$emptyCategory, $filledCategory])
            ->assertTableBulkActionHalted('delete')
            ->assertNotified('Some categories still have products');

        $this->assertDatabaseHas('categories', ['id' => $emptyCategory->id]);
        $this->assertDatabaseHas('categories', ['id' => $filledCategory->id]);
    }

    public function test_bulk_deletes_categories_without_products(): void
    {
        $categories = Category::factory()->count(2)->create();

        Livewire::test(ListCategories::class)
            ->callTableBulkAction('delete', $categories->all());

        $this->assertDatabaseMissing('categories', ['id' => $categories[0]->id]);
        $this->assertDatabaseMissing('categories', ['id' => $categories[1]->id]);
    }

    public function test_database_blocks_deleting_category_with_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();

        $this->expectException(QueryException::class);

        $category->delete();
    }
}

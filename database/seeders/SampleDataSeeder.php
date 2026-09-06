<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Categories
        $electronics = Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Electronic devices and accessories']);
        $clothing = Category::create(['name' => 'Clothing', 'slug' => 'clothing', 'description' => 'Apparel and fashion items']);
        $food = Category::create(['name' => 'Food & Beverages', 'slug' => 'food-beverages', 'description' => 'Food and drink products']);
        $stationery = Category::create(['name' => 'Stationery', 'slug' => 'stationery', 'description' => 'Office and school supplies']);

        // Products
        $products = [
            Product::create(['category_id' => $electronics->id, 'name' => 'Wireless Mouse', 'sku' => 'ELC-001', 'price' => 150000, 'description' => 'Ergonomic wireless mouse']),
            Product::create(['category_id' => $electronics->id, 'name' => 'USB-C Cable', 'sku' => 'ELC-002', 'price' => 50000, 'description' => '1m USB-C charging cable']),
            Product::create(['category_id' => $electronics->id, 'name' => 'Bluetooth Speaker', 'sku' => 'ELC-003', 'price' => 350000, 'description' => 'Portable bluetooth speaker']),
            Product::create(['category_id' => $clothing->id, 'name' => 'Cotton T-Shirt', 'sku' => 'CLT-001', 'price' => 120000, 'description' => 'Plain cotton t-shirt']),
            Product::create(['category_id' => $clothing->id, 'name' => 'Denim Jeans', 'sku' => 'CLT-002', 'price' => 280000, 'description' => 'Classic fit denim jeans']),
            Product::create(['category_id' => $food->id, 'name' => 'Instant Noodles', 'sku' => 'FNB-001', 'price' => 5000, 'description' => 'Indomie Goreng pack']),
            Product::create(['category_id' => $food->id, 'name' => 'Bottled Water', 'sku' => 'FNB-002', 'price' => 3000, 'description' => '600ml mineral water']),
            Product::create(['category_id' => $food->id, 'name' => 'Coffee Sachet', 'sku' => 'FNB-003', 'price' => 2500, 'description' => 'Kopi Susu sachet']),
            Product::create(['category_id' => $stationery->id, 'name' => 'Notebook', 'sku' => 'STN-001', 'price' => 25000, 'description' => 'A5 ruled notebook']),
            Product::create(['category_id' => $stationery->id, 'name' => 'Ballpoint Pen', 'sku' => 'STN-002', 'price' => 3000, 'description' => 'Standard ballpoint pen']),
        ];

        // Stocks per outlet
        $outlets = Outlet::all();

        foreach ($outlets as $outlet) {
            foreach ($products as $product) {
                Stock::create([
                    'product_id' => $product->id,
                    'outlet_id' => $outlet->id,
                    'quantity' => rand(5, 100),
                    'min_stock' => rand(3, 10),
                ]);
            }
        }
    }
}

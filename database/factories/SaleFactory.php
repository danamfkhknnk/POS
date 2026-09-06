<?php

namespace Database\Factories;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->numberBetween(1_000, 1_000_000);

        return [
            'trx_id' => fake()->regexify('TRX-[A-Z0-9]{8}'),
            'product_id' => Product::factory(),
            'outlet_id' => Outlet::factory(),
            'user_id' => User::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $unitPrice * $quantity,
            'sold_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }
}

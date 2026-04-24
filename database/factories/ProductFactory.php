<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $productName = fake()->unique()->words(2, true);

        return [
            'name' => $productName,
            'slug' => Str::slug($productName),
            'category_id' => fake()->randomElement([1, 2, 3, 4, 5]),
            'unit_id' => fake()->randomElement([1, 2, 3]),
            'quantity' => fake()->randomNumber(2),
            'buying_price' => fake()->randomNumber(2),
            'selling_price' => fake()->randomNumber(2),
            'quantity_alert' => fake()->randomElement([5, 10, 15]),
            'tax' => fake()->randomElement([5, 10, 15, 20, 25]),
            'tax_type' => fake()->randomElement([0, 1]),
        ];
    }
}

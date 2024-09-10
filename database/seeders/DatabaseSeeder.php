<?php

namespace Database\Seeders;

use App\Enums\UnitEnum;
use Illuminate\Database\Seeder;
use App\Models\Ingredient;
use App\Models\Product;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        // Seed Ingredients
        $beef = Ingredient::create([
            'name'          => 'Beef',
            'stock'         => 20000, // 20kg in grams
            'initial_stock' => 20000, // Used for calculating alerts
            'unit'          => UnitEnum::GRAMS->value,
        ]);

        $cheese = Ingredient::create([
            'name'          => 'Cheese',
            'stock'         => 5000, 
            'initial_stock' => 5000, 
            'unit'          => UnitEnum::GRAMS->value,
        ]);

        $onion = Ingredient::create([
            'name'          => 'Onion',
            'stock'         => 1000, 
            'initial_stock' => 1000, 
            'unit'          => UnitEnum::GRAMS->value,
        ]);

        // Seed Products
        $burger = Product::create([
            'name'          => 'Burger',
        ]);

        // Attach ingredients to products with the required amounts
        $burger->ingredients()->attach([
            $beef->id       => ['amount' => 150], // 150g Beef per burger
            $cheese->id     => ['amount' => 30],  // 30g Cheese per burger
            $onion->id      => ['amount' => 20],  // 20g Onion per burger
        ]);
    }

}

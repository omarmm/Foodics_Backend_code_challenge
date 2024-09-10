<?php

namespace Database\Factories;

use App\Enums\UnitEnum;
use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;

class IngredientFactory extends Factory
{
    protected $model = Ingredient::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'stock' => $this->faker->numberBetween(50, 200),
            'initial_stock' => $this->faker->numberBetween(50, 200),
            'unit' =>  UnitEnum::GRAMS->value,
            'alert_sent' => false,
        ];
    }
}

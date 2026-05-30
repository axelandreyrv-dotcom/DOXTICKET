<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->randomElement(['Soporte', 'Infraestructura', 'Accesos', 'Hardware', 'Software']),
            'color' => '#E1F3FE',
            'is_active' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\KbArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KbArticle>
 */
class KbArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => fake()->sentence(4),
            'body_markdown' => fake()->paragraph(),
            'status' => 'draft',
            'published_at' => null,
        ];
    }
}

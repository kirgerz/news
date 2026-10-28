<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Article> */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'heading' => fake()->sentence(),
            'text' => fake()->paragraph(),
            'category_id' => Category::factory(),
            'user_id' => User::factory()->journalist(),
        ];
    }
}

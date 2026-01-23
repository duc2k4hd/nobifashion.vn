<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(6);

        return [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(4),
            'content' => $this->faker->paragraphs(4, true),
            'excerpt' => $this->faker->paragraph(),
            'status' => 'draft',
            'is_featured' => false,
            'views' => 0,
            'account_id' => Account::factory(),
            'category_id' => Category::factory(),
            'created_by' => Account::factory(),
        ];
    }

    public function published(): self
    {
        return $this->state(function () {
            return [
                'status' => 'published',
                'published_at' => now()->subDay(),
            ];
        });
    }
}

<?php

namespace Database\Factories;

use App\Models\Tag;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->word();
        
        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
            'usage_count' => $this->faker->numberBetween(0, 100),
            'entity_type' => Post::class,
            'entity_id' => Post::factory(),
        ];
    }

    /**
     * Tag for product
     */
    public function forProduct(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => Product::class,
            'entity_id' => Product::factory(),
        ]);
    }

    /**
     * Tag for post
     */
    public function forPost(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => Post::class,
            'entity_id' => Post::factory(),
        ]);
    }

    /**
     * Inactive tag
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

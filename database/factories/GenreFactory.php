<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Genre>
 */
class GenreFactory extends Factory
{
    /**
     * ジャンルの初期値を定義する。
     *
     * name は一意制約があるため、連番を付与して衝突を避ける。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'ジャンル'.fake()->unique()->numberBetween(1, 100000),
        ];
    }
}

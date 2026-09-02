<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * 書籍の初期値を定義する。
     *
     * user_id は登録者で、未指定なら User を新規作成して紐付ける。
     * isbn は一意制約があるため13桁の重複しない数字列を生成する。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'isbn' => fake()->unique()->numerify('#############'),
            'published_date' => fake()->date(),
            'description' => fake()->paragraph(),
            'image_url' => 'https://placehold.co/200x300',
        ];
    }
}

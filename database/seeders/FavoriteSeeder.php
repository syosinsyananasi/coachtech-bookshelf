<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * お気に入りの初期データを投入する。
     *
     * 各ユーザーに 3〜5 冊の書籍をランダムにお気に入り登録する。
     * syncWithoutDetaching を用いるため、繰り返し実行しても中間テーブルの行が重複せず、
     * 既存のお気に入りも解除されない。
     */
    public function run(): void
    {
        $books = Book::all();

        User::all()->each(function (User $user) use ($books) {
            $bookIds = $books->random(rand(3, 5))->pluck('id');

            $user->favoriteBooks()->syncWithoutDetaching($bookIds);
        });
    }
}

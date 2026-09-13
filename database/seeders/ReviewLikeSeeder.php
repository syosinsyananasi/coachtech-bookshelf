<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * レビューへのいいねの初期データを投入する。
     *
     * 各レビューに 0〜3 人のユーザーがいいねする。投稿者自身は候補から除外する。
     * syncWithoutDetaching を用いるため、繰り返し実行しても中間テーブルの行が重複せず、
     * 既存のいいねも解除されない。
     */
    public function run(): void
    {
        $users = User::all();

        Review::all()->each(function (Review $review) use ($users) {
            $likerIds = $users
                ->where('id', '!=', $review->user_id)
                ->random(rand(0, 3))
                ->pluck('id');

            $review->likedByUsers()->syncWithoutDetaching($likerIds);
        });
    }
}

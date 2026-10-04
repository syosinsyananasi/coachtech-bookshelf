<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ReviewSeeder extends Seeder
{
    /**
     * 評価別のコメントのテンプレート（★応用のシーディング要件）。
     *
     * @var array<int, array<int, string>>
     */
    private const COMMENTS = [
        5 => ['素晴らしい本でした！', '人生が変わりました。', '何度も読み返しています。'],
        4 => ['とても参考になりました。', '読みやすくておすすめです。', '期待通りの内容でした。'],
        3 => ['普通でした。', '可もなく不可もなく。', '期待したほどではなかった。'],
        2 => ['少し期待外れでした。', '内容が薄い印象。', 'もう少し深掘りしてほしかった。'],
        1 => ['残念ながら合いませんでした。', '期待と違いました。'],
    ];

    /**
     * レビューの初期データを投入する。
     *
     * ★応用: 各書籍に 2〜4 件（rand(2, 4)）をランダムなユーザーが投稿し、評価は 1〜5 の全範囲、
     * コメントは評価別のテンプレートから選ぶ（マイ読書レポートの評価分布が意味のある分布になるため。
     * 基本機能では 32 件・評価 3〜5 の固定データだった）。
     * create を用いるため、繰り返し実行すると重複する（投入し直す場合は migrate:fresh --seed を使う）。
     */
    public function run(): void
    {
        $users = User::all();

        Book::all()->each(fn (Book $book) => $this->seedReviewsFor($book, $users));
    }

    /**
     * 1 冊の書籍に 2〜4 件のレビューを投入する。
     *
     * 投稿者は重複しないよう $users->random($count) で選ぶ（同じ人が同じ本に 2 件書かない）。
     *
     * @param  Collection<int, User>  $users  投稿者の候補
     */
    private function seedReviewsFor(Book $book, Collection $users): void
    {
        $users->random(rand(2, 4))->each(function (User $user) use ($book) {
            $rating = rand(1, 5);

            Review::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $rating,
                'comment' => Arr::random(self::COMMENTS[$rating]),
            ]);
        });
    }
}

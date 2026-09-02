<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    /**
     * ジャンルマスタの初期データを投入する。
     *
     * 要件で定められた10件を固定で登録する。firstOrCreate を用いるため、
     * 繰り返し実行してもジャンル名が重複しない。
     */
    public function run(): void
    {
        $genres = [
            '小説',
            'ビジネス',
            '技術書',
            '自己啓発',
            'エッセイ',
            '歴史',
            '科学',
            '芸術',
            '料理',
            '旅行',
        ];
        foreach ($genres as $genre) {
            Genre::firstOrCreate(['name' => $genre]);
        }
    }
}

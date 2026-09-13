<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * アプリケーションの初期データを投入する。
     *
     * 外部キーの依存関係を考慮し、ユーザー → ジャンル → 書籍 → レビュー の順で呼び出す。
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            GenreSeeder::class,
            BookSeeder::class,
            ReviewSeeder::class,
        ]);
    }
}

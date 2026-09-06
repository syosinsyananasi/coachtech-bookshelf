<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * 初期ユーザーを投入する。
     *
     * 要件で定められた5件を固定で登録する。firstOrCreate を用いるため、
     * 繰り返し実行してもメールアドレスが重複しない。
     */
    public function run(): void
    {
        $users = [
            ['name' => '山田太郎', 'email' => 'yamada@example.com'],
            ['name' => '鈴木花子', 'email' => 'suzuki@example.com'],
            ['name' => '田中一郎', 'email' => 'tanaka@example.com'],
            ['name' => '佐藤美咲', 'email' => 'sato@example.com'],
            ['name' => '高橋健太', 'email' => 'takahashi@example.com'],
        ];
        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                ['name' => $user['name'], 'password' => Hash::make('password')],
            );
        }
    }
}

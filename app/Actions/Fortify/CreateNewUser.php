<?php

namespace App\Actions\Fortify;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Fortify の会員登録（POST /register）でユーザーを作成するアクション。
 *
 * バリデーションは RegisterRequest に分離し、本クラスはユーザーの作成のみを担当する。
 */
class CreateNewUser implements CreatesNewUsers
{
    /**
     * 入力値を検証し、新規ユーザーを作成する。
     *
     * Fortify は FormRequest を受け取れないため、コンテナから RegisterRequest を解決して検証する。
     * 解決時に authorize() と rules() が自動で実行され、失敗時は ValidationException で登録フォームへ戻る。
     * パスワードは Hash::make() でハッシュ化して保存する。
     *
     * @param  array<string, string>  $input  登録フォームの入力値（検証は RegisterRequest が行う）
     * @return User 作成したユーザー
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(array $input): User
    {
        $validated = app(RegisterRequest::class)->validated();

        return User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
    }
}

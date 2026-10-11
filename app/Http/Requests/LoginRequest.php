<?php

namespace App\Http\Requests;

use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

/**
 * ログイン（PG12）のバリデーション。
 *
 * Fortify 同梱の LoginRequest（email / password とも required / string）を継承し、
 * PM 回答（2026-08-25「4. LoginRequest」）のとおり email は required / email、password は required に差し替える。
 * FortifyServiceProvider でコンテナに紐付けることで、Fortify のログイン処理がこのクラスを使うようになる。
 */
class LoginRequest extends FortifyLoginRequest
{
    /**
     * ログインのバリデーションルールを返す。
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ];
    }

    /**
     * 日本語のバリデーションメッセージを返す。
     *
     * @return array<string, string> ルール名をキーとしたメッセージ
     */
    public function messages(): array
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスはメール形式で入力してください',
            'password.required' => 'パスワードを入力してください',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 会員登録（PG13）のバリデーション。
 *
 * Fortify は登録処理を CreateNewUser に委ねるため、CreateNewUser からコンテナ経由で本クラスを解決して検証する。
 */
class RegisterRequest extends FormRequest
{
    /**
     * このリクエストの実行を許可するか判定する。
     *
     * 会員登録は誰でも行えるため、常に許可する。
     *
     * @return bool 常に true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 会員登録のバリデーションルールを返す。
     *
     * メールアドレスは既存ユーザーと重複してはならない。
     * パスワードは 8文字 以上 255文字 以内で、確認用と一致すること。
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
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
            'name.required' => 'お名前を入力してください',
            'name.max' => 'お名前は255文字以内で入力してください。',
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスはメール形式で入力してください',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'email.unique' => 'そのメールアドレスは既に使用されています。',
            'password.required' => 'パスワードを入力してください',
            'password.confirmed' => 'パスワードと一致しません',
            'password.min' => 'パスワードは8文字以上で入力してください',
            'password.max' => 'パスワードは255文字以内で入力してください。',
        ];
    }
}

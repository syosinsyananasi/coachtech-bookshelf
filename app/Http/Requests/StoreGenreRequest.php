<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGenreRequest extends FormRequest
{
    /**
     * このリクエストの実行を許可するか判定する。
     *
     * ジャンルには所有者などの認可要件が無く、認証は routes/web.php の
     * auth ミドルウェアで担保しているため、常に許可する。
     *
     * @return bool 常に true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * ジャンル登録のバリデーションルールを返す。
     *
     * ジャンル名は必須・255文字以内で、既存のジャンル名と重複してはならない。
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:genres,name',
            ],
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
            'name.required' => 'ジャンル名は必須です。',
            'name.string' => 'ジャンル名は文字列で入力してください。',
            'name.max' => 'ジャンル名は255文字以内で入力してください。',
            'name.unique' => 'そのジャンル名は既に使用されています。',
        ];
    }
}

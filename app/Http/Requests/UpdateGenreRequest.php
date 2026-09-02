<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGenreRequest extends FormRequest
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
     * ジャンル編集のバリデーションルールを返す。
     *
     * 登録時と同等だが、一意性チェックでは編集中のレコード自身を ignore() で除外する。
     * 除外しないと自分自身にヒットし、名前を変更しない更新が必ず失敗する。
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
                Rule::unique('genres')->ignore($this->genre),
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

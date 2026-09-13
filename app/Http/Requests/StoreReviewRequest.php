<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    /**
     * このリクエストの実行を許可するか判定する。
     *
     * レビュー投稿は所有者チェックが不要（投稿者はログインユーザー自身）で、
     * 認証は routes/web.php の auth ミドルウェアで担保する。
     *
     * @return bool 許可する場合 true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * レビュー投稿のバリデーションルールを返す。
     *
     * 評価は 1〜5 の整数、コメントは 1000 文字以内の必須入力とする。
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:1000'],
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
            'rating.required' => '評価は必須です。',
            'rating.integer' => '評価は整数で入力してください。',
            'rating.min' => '評価は1〜5の整数で入力してください。',
            'rating.max' => '評価は1〜5の整数で入力してください。',
            'comment.required' => 'コメントは必須です。',
            'comment.string' => 'コメントは文字列で入力してください。',
            'comment.max' => 'コメントは1000文字以内で入力してください。',
        ];
    }
}

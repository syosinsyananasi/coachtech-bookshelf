<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

/**
 * ★ ISBN 検索（GET /books/isbn/{isbn}）のバリデーション。
 *
 * 対象はパスパラメータの isbn。ルール・文言は書籍登録の isbn（string / size:13）と同じものを流用する。
 * 画面の JavaScript が事前に 13 桁チェックを行うため、サーバー側は URL 直打ち対策として機能する。
 */
class IsbnSearchRequest extends FormRequest
{
    /**
     * このリクエストの実行を許可するか判定する。
     *
     * 認証はルートの auth ミドルウェアで行うため、ここでは常に許可する。
     *
     * @return bool 常に true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * パスパラメータの isbn をバリデーション対象の入力に含める。
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['isbn' => $this->route('isbn')]);
    }

    /**
     * バリデーションルールを返す。
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'isbn' => ['required', 'string', 'size:13'],
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
            'isbn.required' => 'ISBNを入力してください。',
            'isbn.string' => 'ISBNは文字列で入力してください。',
            'isbn.size' => 'ISBNは13桁で入力してください。',
        ];
    }

    /**
     * バリデーション失敗時は 400 と {"error": "…"} の JSON を返す（PM 回答 Q-017-1）。
     *
     * 画面の JavaScript が error キーの有無で成否を判定するため、Laravel 標準の 422 / {"message", "errors"} 形式は使わない。
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['error' => $validator->errors()->first()], Response::HTTP_BAD_REQUEST)
        );
    }
}

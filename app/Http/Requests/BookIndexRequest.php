<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * ★ 書籍一覧（PG01）の検索・絞り込み・ソートのバリデーション。
 *
 * パラメータ名と sort の値は Advanced ブランチの Blade（keyword / genre / sort）に合わせる。
 * すべて任意で、指定が無ければ全件を最新順に表示する。
 * ルール・文言は PM 回答（Q-016-1）により自身の判断で確定。
 */
class BookIndexRequest extends FormRequest
{
    /**
     * このリクエストの実行を許可するか判定する。
     *
     * 一覧は公開ページのため、常に許可する。
     *
     * @return bool 常に true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 検索パラメータのバリデーションルールを返す。
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'genre' => ['nullable', 'integer', 'exists:genres,id'],
            'sort' => ['nullable', 'string', 'in:newest,oldest,rating,title'],
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
            'keyword.string' => 'キーワードは文字列で入力してください。',
            'keyword.max' => 'キーワードは255文字以内で入力してください。',
            'genre.integer' => 'ジャンルIDは整数で入力してください。',
            'genre.exists' => '指定されたジャンルは存在しません。',
            'sort.string' => '並び順は文字列で入力してください。',
            'sort.in' => '並び順の指定が正しくありません。',
        ];
    }

    /**
     * バリデーション失敗時は条件なしの一覧へ戻し、先頭のエラー文言をフラッシュで表示する。
     *
     * 一覧 Blade には項目別のエラー表示が無く session('error') の枠だけがあるため、
     * 既定の「元の URL へ戻して $errors を渡す」挙動を差し替える（PM 回答 Q-016-2 により自身の判断で確定）。
     * 想定される場面は URL の直接編集や、ブックマーク後にジャンルが削除された場合。
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            redirect()->route('books.index')->with('error', $validator->errors()->first())
        );
    }
}

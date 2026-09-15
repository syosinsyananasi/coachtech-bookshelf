<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 公開API 書籍一覧（AP01）の検索パラメータのバリデーション。
 *
 * すべて任意。指定が無ければ全件を最新順に 20件/ページ で返す。
 */
class IndexBookRequest extends FormRequest
{
    /**
     * このリクエストの実行を許可するか判定する。
     *
     * 一覧は公開 API のため、常に許可する。
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
     * keyword はタイトル・著者名の部分一致、genre_id は既存ジャンルによる絞り込み。
     * per_page は過大な取得を防ぐため上限を 100 とする。
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'genre_id' => ['nullable', 'integer', 'exists:genres,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
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
            'genre_id.integer' => 'ジャンルIDは整数で入力してください。',
            'genre_id.exists' => '指定されたジャンルは存在しません。',
            'page.integer' => 'ページ番号は整数で入力してください。',
            'page.min' => 'ページ番号は1以上で入力してください。',
            'per_page.integer' => '1ページあたりの件数は整数で入力してください。',
            'per_page.min' => '1ページあたりの件数は1以上で入力してください。',
            'per_page.max' => '1ページあたりの件数は100以下で入力してください。',
        ];
    }
}

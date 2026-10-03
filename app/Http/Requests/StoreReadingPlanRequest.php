<?php

namespace App\Http\Requests;

use App\Enums\ReadingPlanStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ★ 読書計画作成（POST /reading-plans）のバリデーション。
 *
 * ルール・メッセージは PM 回答（バリデーション No.16 / メッセージ No.5）のとおり。
 * 重複制御は「同じ書籍で進行中の計画が既にあれば不可」とし、完了済み・期限切れの書籍は再計画できる。
 */
class StoreReadingPlanRequest extends FormRequest
{
    /**
     * このリクエストの実行を許可するか判定する。
     *
     * 認証はルートの auth ミドルウェアで行い、作成は誰でもできるため常に許可する。
     *
     * @return bool 常に true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 読書計画作成のバリデーションルールを返す。
     *
     * book_id の unique は「ログインユーザーの進行中の計画」に絞って判定する。
     * target_date は今日以降（当日は「今日中に読む」として許可）。
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'book_id' => [
                'required',
                'integer',
                'exists:books,id',
                Rule::unique('reading_plans', 'book_id')
                    ->where('user_id', $this->user()->id)
                    ->where('status', ReadingPlanStatus::InProgress->value),
            ],
            'target_date' => ['required', 'date', 'after_or_equal:today'],
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
            'book_id.required' => '書籍を選択してください。',
            'book_id.integer' => '書籍IDは整数で入力してください。',
            'book_id.exists' => '選択された書籍は存在しません。',
            'book_id.unique' => 'この書籍は既に進行中の読書計画が存在します。',
            'target_date.required' => '期日は必須です。',
            'target_date.date' => '期日は有効な日付形式で入力してください。',
            'target_date.after_or_equal' => '期日は今日以降の日付を指定してください。',
        ];
    }
}

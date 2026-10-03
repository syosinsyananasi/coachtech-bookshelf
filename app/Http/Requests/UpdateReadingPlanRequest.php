<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ★ 読書計画編集（PUT /reading-plans/{readingPlan}）のバリデーション。
 *
 * 変更できるのは期日だけ（編集 Blade のフォームが target_date のみ）。
 * ルール・メッセージは PM 回答（バリデーション No.17 / メッセージ No.5）のとおり。
 * 所有者・状態（完了済みは編集不可）の制限は ReadingPlanPolicy で行う。
 */
class UpdateReadingPlanRequest extends FormRequest
{
    /**
     * このリクエストの実行を許可するか判定する。
     *
     * 認可はコントローラの authorize()（ReadingPlanPolicy::update）で行うため、ここでは常に許可する。
     *
     * @return bool 常に true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 読書計画編集のバリデーションルールを返す。
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
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
            'target_date.required' => '期日は必須です。',
            'target_date.date' => '期日は有効な日付形式で入力してください。',
            'target_date.after_or_equal' => '期日は今日以降の日付を指定してください。',
        ];
    }
}

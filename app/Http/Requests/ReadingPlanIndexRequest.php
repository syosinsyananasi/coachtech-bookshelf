<?php

namespace App\Http\Requests;

use App\Enums\ReadingPlanStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * ★ 読書計画一覧（PG15）の状態による絞り込みのバリデーション。
 *
 * status は任意で、値は ReadingPlanStatus の 3 つ（in_progress / completed / expired）のみ。
 * 一覧 Blade のプルダウンが送る値と一致させる。
 */
class ReadingPlanIndexRequest extends FormRequest
{
    /**
     * このリクエストの実行を許可するか判定する。
     *
     * 認証はルートの auth ミドルウェアで行い、表示するのは自分の計画だけなので、常に許可する。
     *
     * @return bool 常に true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 絞り込みパラメータのバリデーションルールを返す。
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(array_column(ReadingPlanStatus::cases(), 'value'))],
        ];
    }

    /**
     * 日本語のバリデーションメッセージを返す。
     *
     * 一覧 Blade にエラーの表示枠が無いため画面には出ないが、全 FormRequest に messages() を定義する規約に従う。
     *
     * @return array<string, string> ルール名をキーとしたメッセージ
     */
    public function messages(): array
    {
        return [
            'status.string' => '状態の指定が正しくありません。',
            'status.in' => '状態の指定が正しくありません。',
        ];
    }

    /**
     * バリデーション失敗時は絞り込みなしの一覧へ戻す。
     *
     * 起きるのは URL を直接編集した場合だけで、一覧 Blade にエラーの表示枠が無いためフラッシュは付けない。
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(redirect()->route('reading-plans.index'));
    }
}

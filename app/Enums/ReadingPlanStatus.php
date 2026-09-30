<?php

namespace App\Enums;

/**
 * 読書計画の状態（★応用）。
 *
 * reading_plans.status に文字列で保存し、Eloquent の casts で相互変換する。
 * 一覧 Blade が cases() / label() / badgeClass() を参照する。
 */
enum ReadingPlanStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Expired = 'expired';

    /**
     * 画面表示用の名称を返す。
     */
    public function label(): string
    {
        return match ($this) {
            self::InProgress => '進行中',
            self::Completed => '完了',
            self::Expired => '期限切れ',
        };
    }

    /**
     * 一覧のバッジに付ける Tailwind クラスを返す。
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::InProgress => 'bg-blue-100 text-blue-800',
            self::Completed => 'bg-green-100 text-green-800',
            self::Expired => 'bg-red-100 text-red-800',
        };
    }
}

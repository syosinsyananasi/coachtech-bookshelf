<?php

namespace App\Enums;

/**
 * 読書計画リマインダー通知の種類（★応用）。
 *
 * 値は通知データの timing に保存し、通知一覧 Blade がアイコンと色の出し分けに使う。
 * 見出し・本文の文言は PM 回答（Q-020-3）のとおり。
 */
enum ReminderTiming: string
{
    case ThreeDaysBefore = 'three_days_before';
    case OnDueDate = 'on_due_date';
    case ThreeDaysAfter = 'three_days_after';

    /**
     * 「期日 − バッチ実行日」の日数を返す。
     *
     * 3 日前は +3、当日は 0、3 日後は −3。日次バッチが対象の計画を探すときに使う。
     */
    public function daysFromTargetDate(): int
    {
        return match ($this) {
            self::ThreeDaysBefore => 3,
            self::OnDueDate => 0,
            self::ThreeDaysAfter => -3,
        };
    }

    /**
     * この通知を送る対象の計画の状態を返す。
     *
     * 3 日前・当日は進行中の計画、3 日後（再エンゲージメント）は期限切れになった計画が対象。完了済みはどの通知も送らない。
     */
    public function targetStatus(): ReadingPlanStatus
    {
        return match ($this) {
            self::ThreeDaysBefore, self::OnDueDate => ReadingPlanStatus::InProgress,
            self::ThreeDaysAfter => ReadingPlanStatus::Expired,
        };
    }

    /**
     * 通知の見出しを返す。
     */
    public function title(): string
    {
        return match ($this) {
            self::ThreeDaysBefore => '読書計画リマインド — 期限まであと3日',
            self::OnDueDate => '読書計画 — 本日が期限',
            self::ThreeDaysAfter => '読書計画 — 期限超過3日経過',
        };
    }

    /**
     * 通知の本文を返す。
     *
     * @param  string  $bookTitle  対象書籍のタイトル
     */
    public function body(string $bookTitle): string
    {
        return match ($this) {
            self::ThreeDaysBefore => "『{$bookTitle}』の期限まで残り3日です。引き続き読書を進めましょう。",
            self::OnDueDate => "『{$bookTitle}』は本日が期限です。読了済みなら完了登録を、もう少し必要なら期限を変更してください。",
            self::ThreeDaysAfter => "『{$bookTitle}』の期限から3日が経過しました。読了済みなら完了登録、続けるなら期限を変更してください。",
        };
    }
}

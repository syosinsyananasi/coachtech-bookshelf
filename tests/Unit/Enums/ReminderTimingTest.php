<?php

namespace Tests\Unit\Enums;

use App\Enums\ReadingPlanStatus;
use App\Enums\ReminderTiming;
use PHPUnit\Framework\TestCase;

/**
 * ReminderTiming Enum（★応用）。リマインダー 3 回（3 日前・当日・3 日後）それぞれの
 * 期日からの日数、対象となる計画の状態、通知の文言（PM 回答 Q-020-3）を検証する。
 */
class ReminderTimingTest extends TestCase
{
    /**
     * 期日からの日数が 3 日前 = +3、当日 = 0、3 日後 = -3 であること（バッチ実行日 + 日数 = 期日）。
     */
    public function test_days_from_target_date(): void
    {
        $this->assertSame(3, ReminderTiming::ThreeDaysBefore->daysFromTargetDate());
        $this->assertSame(0, ReminderTiming::OnDueDate->daysFromTargetDate());
        $this->assertSame(-3, ReminderTiming::ThreeDaysAfter->daysFromTargetDate());
    }

    /**
     * 3 日前・当日は進行中の計画、3 日後は期限切れの計画が通知対象であること。
     */
    public function test_target_status(): void
    {
        $this->assertSame(ReadingPlanStatus::InProgress, ReminderTiming::ThreeDaysBefore->targetStatus());
        $this->assertSame(ReadingPlanStatus::InProgress, ReminderTiming::OnDueDate->targetStatus());
        $this->assertSame(ReadingPlanStatus::Expired, ReminderTiming::ThreeDaysAfter->targetStatus());
    }

    /**
     * title() が PM 回答どおりの見出しを返すこと。
     */
    public function test_title_matches_pm_answer(): void
    {
        $this->assertSame('読書計画リマインド — 期限まであと3日', ReminderTiming::ThreeDaysBefore->title());
        $this->assertSame('読書計画 — 本日が期限', ReminderTiming::OnDueDate->title());
        $this->assertSame('読書計画 — 期限超過3日経過', ReminderTiming::ThreeDaysAfter->title());
    }

    /**
     * body() が書籍タイトルを埋め込んだ PM 回答どおりの本文を返すこと。
     */
    public function test_body_embeds_book_title(): void
    {
        $this->assertSame(
            '『リーダブルコード』の期限まで残り3日です。引き続き読書を進めましょう。',
            ReminderTiming::ThreeDaysBefore->body('リーダブルコード'),
        );
        $this->assertSame(
            '『リーダブルコード』は本日が期限です。読了済みなら完了登録を、もう少し必要なら期限を変更してください。',
            ReminderTiming::OnDueDate->body('リーダブルコード'),
        );
        $this->assertSame(
            '『リーダブルコード』の期限から3日が経過しました。読了済みなら完了登録、続けるなら期限を変更してください。',
            ReminderTiming::ThreeDaysAfter->body('リーダブルコード'),
        );
    }
}

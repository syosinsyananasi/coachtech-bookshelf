<?php

namespace App\Notifications;

use App\Enums\ReminderTiming;
use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * 読書計画のリマインダー通知（★応用）。
 *
 * Laravel 標準の Notification を DatabaseChannel で保存し、通知一覧 Blade が data の
 * timing / title / body を描画する。送るのは日次バッチ（3 日前・当日・3 日後の 3 回、各計画に 1 回ずつ）。
 */
class ReadingPlanReminder extends Notification
{
    use Queueable;

    /**
     * @param  ReadingPlan  $readingPlan  対象の読書計画（book を読み込み済みであること）
     * @param  ReminderTiming  $timing  通知の種類
     */
    public function __construct(
        private readonly ReadingPlan $readingPlan,
        private readonly ReminderTiming $timing,
    ) {}

    /**
     * 配信チャネルを返す。
     *
     * @return array<int, string> database のみ
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * notifications テーブルの data に保存する内容を返す。
     *
     * reading_plan_id と timing は、同じ計画・同じ種類の通知を二重に送らないための判定に使う。
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'reading_plan_id' => $this->readingPlan->id,
            'timing' => $this->timing->value,
            'title' => $this->timing->title(),
            'body' => $this->timing->body($this->readingPlan->book->title),
        ];
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Enums\ReminderTiming;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

/**
 * 読書計画の日次バッチ（★応用）。毎日 20 時に Schedule から実行する（app/Console/Kernel.php）。
 *
 * 1. 期日を過ぎた進行中の計画を期限切れ（expired）にする（PM 回答 Q-020-1 の A: 期日の翌日 20 時）。
 * 2. 3 日前・当日・3 日後（再エンゲージメント）のリマインダー通知を、各計画に 1 回ずつ送る（Q-020-2）。
 *    同じ日に複数回実行されても、同じ計画・同じ種類の通知は二重に送らない。
 * 期限切れ化を先に行うため、期日の 3 日前に作られた進行中の計画は 1 回の実行で期限切れ化と 3 日後通知の両方が起きる（シーディング要件の二重シナリオ）。
 */
class ProcessReadingPlans extends Command
{
    /**
     * コマンド名（sail artisan reading-plans:process）。
     *
     * @var string
     */
    protected $signature = 'reading-plans:process';

    /**
     * コマンドの説明（artisan list に表示）。
     *
     * @var string
     */
    protected $description = '期日を過ぎた読書計画を期限切れにし、3 日前・当日・3 日後のリマインダー通知を送る';

    /**
     * バッチ本体。
     *
     * @return int 終了コード（常に成功）
     */
    public function handle(): int
    {
        $today = Carbon::today();

        $expiredCount = $this->expireOverduePlans($today);
        $sentCount = collect(ReminderTiming::cases())
            ->sum(fn (ReminderTiming $timing) => $this->sendReminders($timing, $today));

        $this->info("期限切れにした計画: {$expiredCount} 件、送った通知: {$sentCount} 件");

        return self::SUCCESS;
    }

    /**
     * 期日を過ぎた進行中の計画を期限切れにする。
     *
     * 期日 < バッチ実行日 の計画が対象。期日当日はまだ進行中のまま（当日リマインダーの対象）。
     *
     * @param  Carbon  $today  バッチ実行日
     * @return int 期限切れにした件数
     */
    private function expireOverduePlans(Carbon $today): int
    {
        return ReadingPlan::where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('target_date', '<', $today)
            ->update(['status' => ReadingPlanStatus::Expired->value]);
    }

    /**
     * 指定した種類のリマインダー通知を、該当する計画の持ち主に送る。
     *
     * 対象は「期日 = バッチ実行日 + 種類ごとの日数差」かつ「種類ごとの対象状態」の計画。
     * 送信済み（同じ計画 ID と種類の通知が既にある）の計画は除外する。
     *
     * @param  ReminderTiming  $timing  通知の種類
     * @param  Carbon  $today  バッチ実行日
     * @return int 送った件数
     */
    private function sendReminders(ReminderTiming $timing, Carbon $today): int
    {
        $plans = ReadingPlan::with('book', 'user.notifications')
            ->where('status', $timing->targetStatus()->value)
            ->whereDate('target_date', $today->copy()->addDays($timing->daysFromTargetDate()))
            ->get()
            ->reject(fn (ReadingPlan $plan) => $this->alreadySent($plan, $timing));

        $plans->each(fn (ReadingPlan $plan) => $plan->user->notify(new ReadingPlanReminder($plan, $timing)));

        return $plans->count();
    }

    /**
     * その計画に同じ種類の通知を送った記録が、持ち主の通知の中にあるかを返す。
     *
     * 通知データの reading_plan_id と timing で判定する（ReadingPlanReminder::toDatabase）。
     */
    private function alreadySent(ReadingPlan $plan, ReminderTiming $timing): bool
    {
        return $plan->user->notifications->contains(
            fn (DatabaseNotification $notification) => ($notification->data['reading_plan_id'] ?? null) === $plan->id
                && ($notification->data['timing'] ?? null) === $timing->value
        );
    }
}

<?php

namespace Tests\Feature\Batch;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * ★ 日次バッチ（reading-plans:process）。期日を過ぎた進行中の計画を期限切れにし、
 * 3 日前・当日・3 日後のリマインダー通知を各計画に 1 回ずつ送る（PM 回答 Q-020）。
 * 日付は Carbon::setTestNow で固定し、通知は notifications テーブルの件数と data で確認する。
 */
class ProcessReadingPlansTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-10 20:00:00');
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * 指定した期日と状態の計画を 1 件作る。
     */
    private function plan(string $targetDate, ReadingPlanStatus $status = ReadingPlanStatus::InProgress): ReadingPlan
    {
        return ReadingPlan::factory()->for($this->user)->create(['target_date' => $targetDate, 'status' => $status]);
    }

    /**
     * 指定した計画・種類の通知が何件あるかを返す。
     */
    private function notificationCount(ReadingPlan $plan, string $timing): int
    {
        return $this->user->notifications()->get()
            ->filter(fn ($notification) => $notification->data['reading_plan_id'] === $plan->id && $notification->data['timing'] === $timing)
            ->count();
    }

    // ---- 期限切れ化（Q-020-1 の A） ----

    /**
     * 期日を過ぎた進行中の計画は期限切れになり、期日当日・未来・完了済みは変わらないこと。
     */
    public function test_overdue_in_progress_plans_are_expired(): void
    {
        $overdue = $this->plan('2026-10-09');
        $dueToday = $this->plan('2026-10-10');
        $future = $this->plan('2026-10-11');
        $completed = $this->plan('2026-10-01', ReadingPlanStatus::Completed);

        $this->artisan('reading-plans:process')->assertSuccessful();

        $this->assertSame(ReadingPlanStatus::Expired, $overdue->refresh()->status);
        $this->assertSame(ReadingPlanStatus::InProgress, $dueToday->refresh()->status);
        $this->assertSame(ReadingPlanStatus::InProgress, $future->refresh()->status);
        $this->assertSame(ReadingPlanStatus::Completed, $completed->refresh()->status);
    }

    // ---- リマインダー通知（Q-020-2 / Q-020-3） ----

    /**
     * 期日が 3 日後の進行中の計画に 3 日前通知が送られ、7 日後の計画には送られないこと。
     */
    public function test_three_days_before_reminder_is_sent(): void
    {
        $target = $this->plan('2026-10-13');
        $notTarget = $this->plan('2026-10-17');

        $this->artisan('reading-plans:process')->assertSuccessful();

        $this->assertSame(1, $this->notificationCount($target, 'three_days_before'));
        $this->assertSame(0, $this->notificationCount($notTarget, 'three_days_before'));
        $this->assertDatabaseCount('notifications', 1);
    }

    /**
     * 期日が当日の進行中の計画に当日通知が送られること。
     */
    public function test_on_due_date_reminder_is_sent(): void
    {
        $target = $this->plan('2026-10-10');

        $this->artisan('reading-plans:process')->assertSuccessful();

        $this->assertSame(1, $this->notificationCount($target, 'on_due_date'));
        $this->assertDatabaseCount('notifications', 1);
    }

    /**
     * 期日の 3 日後に、期限切れの計画へ再エンゲージメント通知が送られること。
     */
    public function test_three_days_after_reminder_is_sent_to_expired_plan(): void
    {
        $target = $this->plan('2026-10-07', ReadingPlanStatus::Expired);

        $this->artisan('reading-plans:process')->assertSuccessful();

        $this->assertSame(1, $this->notificationCount($target, 'three_days_after'));
        $this->assertDatabaseCount('notifications', 1);
    }

    /**
     * 期日の 3 日後にまだ進行中の計画は、1 回の実行で期限切れ化と 3 日後通知の両方が起きること（シーディング要件の二重シナリオ）。
     */
    public function test_double_scenario_expires_and_notifies_in_one_run(): void
    {
        $target = $this->plan('2026-10-07');

        $this->artisan('reading-plans:process')->assertSuccessful();

        $this->assertSame(ReadingPlanStatus::Expired, $target->refresh()->status);
        $this->assertSame(1, $this->notificationCount($target, 'three_days_after'));
    }

    /**
     * 完了済みの計画には、期日が該当日でもどの通知も送られないこと。
     */
    public function test_completed_plans_receive_no_reminders(): void
    {
        $this->plan('2026-10-13', ReadingPlanStatus::Completed);
        $this->plan('2026-10-10', ReadingPlanStatus::Completed);
        $this->plan('2026-10-07', ReadingPlanStatus::Completed);

        $this->artisan('reading-plans:process')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 0);
    }

    /**
     * 同じ日に 2 回実行しても、同じ計画・同じ種類の通知は二重に送られないこと。
     */
    public function test_running_twice_does_not_duplicate_notifications(): void
    {
        $target = $this->plan('2026-10-10');

        $this->artisan('reading-plans:process')->assertSuccessful();
        $this->artisan('reading-plans:process')->assertSuccessful();

        $this->assertSame(1, $this->notificationCount($target, 'on_due_date'));
        $this->assertDatabaseCount('notifications', 1);
    }

    /**
     * 通知の見出しが種類に応じた PM 回答の文言になり、受信者が計画の持ち主であること。
     */
    public function test_notification_content_matches_timing(): void
    {
        $this->plan('2026-10-13');

        $this->artisan('reading-plans:process')->assertSuccessful();

        $notification = $this->user->notifications()->first();
        $this->assertSame('読書計画リマインド — 期限まであと3日', $notification->data['title']);
        $this->assertSame(User::class, $notification->notifiable_type);
    }

    // ---- シーダーのシナリオ ----

    /**
     * シーディング要件の 6 件に対して 1 回実行すると、ID 3 が期限切れになり、山田太郎に 3 日前・当日・3 日後の 3 通が届くこと。
     */
    public function test_seeded_scenarios(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(20, 0));
        $this->seed();

        $this->artisan('reading-plans:process')->assertSuccessful();

        // ID は他のテストの連番が残って一致しないことがあるため、期日で特定する（3 = 3 日前、2 = 当日）
        $yamada = User::firstWhere('email', 'yamada@example.com');
        $plan3 = $yamada->readingPlans()->whereDate('target_date', Carbon::today()->subDays(3))->first();
        $plan2 = $yamada->readingPlans()->whereDate('target_date', Carbon::today())->first();
        $this->assertSame(ReadingPlanStatus::Expired, $plan3->status);
        $this->assertSame(ReadingPlanStatus::InProgress, $plan2->status);

        // 山田太郎に届いた通知の種類（順不同なので並べ替えて比較）
        $timings = $yamada->notifications()->get()
            ->map(fn ($notification) => $notification->data['timing'])
            ->sort()
            ->values()
            ->all();
        $this->assertSame(['on_due_date', 'three_days_after', 'three_days_before'], $timings);
        $this->assertDatabaseCount('notifications', 3);
    }

    // ---- スケジュール ----

    /**
     * バッチが毎日 20 時に登録されていること（app/Console/Kernel.php）。
     */
    public function test_batch_is_scheduled_daily_at20(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command, 'reading-plans:process'));

        $this->assertCount(1, $events);
        $this->assertSame('0 20 * * *', $events->first()->expression);
    }
}

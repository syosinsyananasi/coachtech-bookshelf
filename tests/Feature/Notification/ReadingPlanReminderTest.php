<?php

namespace Tests\Feature\Notification;

use App\Enums\ReminderTiming;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ★ 読書計画リマインダー通知（ReadingPlanReminder）。DatabaseChannel で notifications テーブルに保存され、
 * data の timing / title / body が PM 回答（Q-020-3）の文言になること。
 */
class ReadingPlanReminderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ReadingPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->plan = ReadingPlan::factory()->for($this->user)->for(Book::factory()->create(['title' => 'リーダブルコード']))->create();
    }

    /**
     * 通知が受信者宛てに 1 件保存され、計画 ID と種類が data に入ること。
     */
    public function test_notification_is_stored_for_the_user(): void
    {
        $this->user->notify(new ReadingPlanReminder($this->plan, ReminderTiming::OnDueDate));

        $this->assertDatabaseCount('notifications', 1);
        $notification = $this->user->notifications()->first();
        $this->assertSame($this->plan->id, $notification->data['reading_plan_id']);
        $this->assertSame('on_due_date', $notification->data['timing']);
        $this->assertNull($notification->read_at);
    }

    /**
     * 3 日前の見出しと本文が PM 回答の文言であること。
     */
    public function test_three_days_before_wording(): void
    {
        $this->user->notify(new ReadingPlanReminder($this->plan, ReminderTiming::ThreeDaysBefore));

        $data = $this->user->notifications()->first()->data;
        $this->assertSame('読書計画リマインド — 期限まであと3日', $data['title']);
        $this->assertSame('『リーダブルコード』の期限まで残り3日です。引き続き読書を進めましょう。', $data['body']);
    }

    /**
     * 当日の見出しと本文が PM 回答の文言であること。
     */
    public function test_on_due_date_wording(): void
    {
        $this->user->notify(new ReadingPlanReminder($this->plan, ReminderTiming::OnDueDate));

        $data = $this->user->notifications()->first()->data;
        $this->assertSame('読書計画 — 本日が期限', $data['title']);
        $this->assertSame('『リーダブルコード』は本日が期限です。読了済みなら完了登録を、もう少し必要なら期限を変更してください。', $data['body']);
    }

    /**
     * 3 日後（再エンゲージメント）の見出しと本文が PM 回答の文言であること。
     */
    public function test_three_days_after_wording(): void
    {
        $this->user->notify(new ReadingPlanReminder($this->plan, ReminderTiming::ThreeDaysAfter));

        $data = $this->user->notifications()->first()->data;
        $this->assertSame('読書計画 — 期限超過3日経過', $data['title']);
        $this->assertSame('『リーダブルコード』の期限から3日が経過しました。読了済みなら完了登録、続けるなら期限を変更してください。', $data['body']);
    }
}

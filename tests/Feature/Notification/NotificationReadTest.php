<?php

namespace Tests\Feature\Notification;

use App\Enums\ReminderTiming;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

/**
 * ★ 通知の既読化（POST /notifications/{notification}/read）。受信者本人のみ既読にでき、
 * 一覧へ戻って「通知を既読にしました。」が表示される（PM 回答 Q-007-3）。他ユーザーの通知は 403（Q-019）。
 */
class NotificationReadTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private DatabaseNotification $notification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $plan = ReadingPlan::factory()->for($this->user)->create();
        $this->user->notify(new ReadingPlanReminder($plan, ReminderTiming::ThreeDaysBefore));
        $this->notification = $this->user->notifications()->first();
    }

    /**
     * 既読にすると read_at が入り、一覧へリダイレクトされて成功メッセージが入ること。
     */
    public function test_notification_is_marked_as_read(): void
    {
        $this->actingAs($this->user)
            ->post(route('notifications.read', $this->notification->id))
            ->assertRedirect(route('notifications.index'))
            ->assertSessionHas('success', '通知を既読にしました。');

        $this->assertNotNull($this->notification->refresh()->read_at);
    }

    /**
     * 成功メッセージが一覧画面に描画され、「既読にする」ボタンが消えること。
     */
    public function test_success_message_is_visible_on_index(): void
    {
        $this->actingAs($this->user)
            ->followingRedirects()
            ->post(route('notifications.read', $this->notification->id))
            ->assertOk()
            ->assertSee('通知を既読にしました。')
            ->assertDontSee('既読にする');
    }

    /**
     * 未認証で既読化を呼ぶとログイン画面へリダイレクトされ、未読のままであること。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('notifications.read', $this->notification->id))
            ->assertRedirect(route('login'));

        $this->assertNull($this->notification->refresh()->read_at);
    }

    /**
     * 他ユーザーの通知 ID を指定すると 403 で、未読のままであること（NotificationPolicy::update）。
     */
    public function test_other_user_cannot_mark_as_read(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('notifications.read', $this->notification->id))
            ->assertForbidden();

        $this->assertNull($this->notification->refresh()->read_at);
    }

    /**
     * 存在しない通知 ID を指定すると 404 になること。
     */
    public function test_unknown_notification_returns404(): void
    {
        $this->actingAs($this->user)
            ->post(route('notifications.read', 'no-such-id'))
            ->assertNotFound();
    }
}

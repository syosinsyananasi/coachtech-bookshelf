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
 * ★PG18 通知一覧。認証必須で、ログインユーザー宛ての通知だけを表示し、未読件数をヘッダーに出す。
 */
class NotificationIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * 指定ユーザーに、指定タイトルの書籍の当日リマインダーを 1 件送る。
     */
    private function notify(User $user, string $bookTitle): void
    {
        $plan = ReadingPlan::factory()->for($user)->for(Book::factory()->create(['title' => $bookTitle]))->create();

        $user->notify(new ReadingPlanReminder($plan, ReminderTiming::OnDueDate));
    }

    /**
     * 未認証でアクセスするとログイン画面へリダイレクトされること。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));
    }

    /**
     * 自分宛ての通知の見出しと本文が表示され、他人宛ての通知は表示されないこと。
     */
    public function test_only_own_notifications_are_displayed(): void
    {
        $this->notify($this->user, '自分の本');
        $this->notify(User::factory()->create(), '他人の本');

        $this->actingAs($this->user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('読書計画 — 本日が期限')
            ->assertSee('『自分の本』は本日が期限です。')
            ->assertDontSee('他人の本');
    }

    /**
     * 未読の通知には「既読にする」ボタンが表示されること。
     */
    public function test_unread_notification_shows_read_button(): void
    {
        $this->notify($this->user, 'リーダブルコード');
        $notification = $this->user->notifications()->first();

        $this->actingAs($this->user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('未読')
            ->assertSee(route('notifications.read', $notification->id), false);
    }

    /**
     * ヘッダーに未読件数が表示されること（navigation の unreadNotifications）。
     */
    public function test_unread_count_is_displayed_in_header(): void
    {
        $this->notify($this->user, '1冊目');
        $this->notify($this->user, '2冊目');

        $this->actingAs($this->user)
            ->get(route('books.index'))
            ->assertOk()
            ->assertSee('rounded-full">2</span>', false);
    }

    /**
     * 通知が 1 件も無いとき、空状態の文言が表示されること。
     */
    public function test_empty_message_is_displayed(): void
    {
        $this->actingAs($this->user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('通知はありません。');
    }
}

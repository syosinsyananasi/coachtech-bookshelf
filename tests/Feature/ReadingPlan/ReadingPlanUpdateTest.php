<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ★PG17 読書計画編集。所有者本人かつ完了済みでない計画の期日だけを変更でき、
 * 一覧へ戻って「読書計画を更新しました。」が表示される（PM 回答 Q-007-2）。
 * バリデーションは UpdateReadingPlanRequest のルールとメッセージ（PM 回答）に対応させる。認可の検証は ReadingPlanAuthorizationTest。
 */
class ReadingPlanUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private ReadingPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->plan = ReadingPlan::factory()
            ->for($this->owner)
            ->for(Book::factory()->create(['title' => 'リーダブルコード']))
            ->create(['target_date' => '2026-12-01']);
    }

    /**
     * 編集画面に書籍名・現在の状態・既存の期日が初期値として表示されること。
     */
    public function test_edit_screen_shows_current_values(): void
    {
        $this->actingAs($this->owner)
            ->get(route('reading-plans.edit', $this->plan))
            ->assertOk()
            ->assertSee('読書計画編集')
            ->assertSee('リーダブルコード')
            ->assertSee('進行中')
            ->assertSee('value="2026-12-01"', false);
    }

    /**
     * 期日を変更すると更新され、一覧へリダイレクトされて成功メッセージが入ること。
     */
    public function test_target_date_is_updated(): void
    {
        $newDate = now()->addMonth()->toDateString();

        $this->actingAs($this->owner)
            ->put(route('reading-plans.update', $this->plan), ['target_date' => $newDate])
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success', '読書計画を更新しました。');

        $this->assertDatabaseHas('reading_plans', ['id' => $this->plan->id, 'target_date' => $newDate]);
    }

    /**
     * 成功メッセージが一覧画面に描画されること。
     */
    public function test_success_message_is_visible_on_index(): void
    {
        $this->actingAs($this->owner)
            ->followingRedirects()
            ->put(route('reading-plans.update', $this->plan), ['target_date' => now()->addMonth()->toDateString()])
            ->assertOk()
            ->assertSee('読書計画を更新しました。');
    }

    /**
     * 期限切れの計画の期日を今日以降に変更すると、進行中に戻ること。
     */
    public function test_expired_plan_returns_to_in_progress_when_updated(): void
    {
        $expired = ReadingPlan::factory()->for($this->owner)->expired()->create();

        $this->actingAs($this->owner)
            ->put(route('reading-plans.update', $expired), ['target_date' => now()->addWeek()->toDateString()])
            ->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', ['id' => $expired->id, 'status' => ReadingPlanStatus::InProgress->value]);
    }

    /**
     * 期日が未入力のとき required エラーになること。
     */
    public function test_target_date_is_required(): void
    {
        $this->actingAs($this->owner)
            ->put(route('reading-plans.update', $this->plan), ['target_date' => ''])
            ->assertSessionHasErrors(['target_date' => '期日は必須です。']);

        $this->assertDatabaseHas('reading_plans', ['id' => $this->plan->id, 'target_date' => '2026-12-01']);
    }

    /**
     * 期日が日付でないとき date エラーになること。
     */
    public function test_target_date_must_be_valid_date(): void
    {
        $this->actingAs($this->owner)
            ->put(route('reading-plans.update', $this->plan), ['target_date' => 'not-a-date'])
            ->assertSessionHasErrors(['target_date' => '期日は有効な日付形式で入力してください。']);
    }

    /**
     * 期日が過去日のとき after_or_equal エラーになること。
     */
    public function test_target_date_cannot_be_in_the_past(): void
    {
        $this->actingAs($this->owner)
            ->put(route('reading-plans.update', $this->plan), ['target_date' => now()->subDay()->toDateString()])
            ->assertSessionHasErrors(['target_date' => '期日は今日以降の日付を指定してください。']);
    }

    /**
     * バリデーション失敗時、編集フォームにエラーメッセージが実際に描画されること。
     */
    public function test_validation_error_is_visible_on_screen(): void
    {
        $this->actingAs($this->owner)
            ->from(route('reading-plans.edit', $this->plan))
            ->followingRedirects()
            ->put(route('reading-plans.update', $this->plan), ['target_date' => ''])
            ->assertOk()
            ->assertSee('期日は必須です。');
    }
}

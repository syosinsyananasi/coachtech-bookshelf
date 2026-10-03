<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ★ 読書計画の読了（POST /reading-plans/{readingPlan}/complete）。
 * status が completed になり completed_at に現在日時が入り、一覧へ戻って「読書計画を完了しました。」が表示される（PM 回答 Q-006-1）。
 * 認可の検証は ReadingPlanAuthorizationTest。
 */
class ReadingPlanCompleteTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
    }

    /**
     * 進行中の計画を読了にすると completed になり、completed_at が入ること。
     */
    public function test_in_progress_plan_is_completed(): void
    {
        $plan = ReadingPlan::factory()->for($this->owner)->create();

        $this->actingAs($this->owner)
            ->post(route('reading-plans.complete', $plan))
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success', '読書計画を完了しました。');

        $plan->refresh();
        $this->assertSame(ReadingPlanStatus::Completed, $plan->status);
        $this->assertNotNull($plan->completed_at);
        $this->assertTrue($plan->completed_at->isToday());
    }

    /**
     * 期限切れの計画も読了にできること（Blade は完了済み以外に「読了する」を表示する）。
     */
    public function test_expired_plan_can_be_completed(): void
    {
        $plan = ReadingPlan::factory()->for($this->owner)->expired()->create();

        $this->actingAs($this->owner)
            ->post(route('reading-plans.complete', $plan))
            ->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', ['id' => $plan->id, 'status' => ReadingPlanStatus::Completed->value]);
    }

    /**
     * 成功メッセージが一覧画面に描画され、状態が「完了」で表示されること。
     */
    public function test_success_message_is_visible_on_index(): void
    {
        $plan = ReadingPlan::factory()->for($this->owner)->create();

        $this->actingAs($this->owner)
            ->followingRedirects()
            ->post(route('reading-plans.complete', $plan))
            ->assertOk()
            ->assertSee('読書計画を完了しました。')
            ->assertSee('完了');
    }
}

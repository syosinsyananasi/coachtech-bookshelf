<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 読書計画の認証・認可（ReadingPlanPolicy）。
 * 認証: 編集・更新・読了・削除は未認証なら /login へ。
 * 認可: 所有者以外は 403。編集・読了は完了済みの計画にも 403（PM 回答: 「所有者 かつ completed でない」）。削除は状態を問わない。
 */
class ReadingPlanAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $other;

    private ReadingPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->other = User::factory()->create();
        $this->plan = ReadingPlan::factory()->for($this->owner)->create(['target_date' => '2026-12-01']);
    }

    // ---- 認証 ----

    /**
     * 未認証で編集・更新・読了・削除を呼ぶとログイン画面へリダイレクトされ、計画が変わらないこと。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reading-plans.edit', $this->plan))->assertRedirect(route('login'));
        $this->put(route('reading-plans.update', $this->plan), ['target_date' => now()->addWeek()->toDateString()])->assertRedirect(route('login'));
        $this->post(route('reading-plans.complete', $this->plan))->assertRedirect(route('login'));
        $this->delete(route('reading-plans.destroy', $this->plan))->assertRedirect(route('login'));

        $this->assertDatabaseHas('reading_plans', ['id' => $this->plan->id, 'target_date' => '2026-12-01', 'status' => ReadingPlanStatus::InProgress->value]);
    }

    // ---- 所有者以外 ----

    /**
     * 他人の計画の編集画面は 403 になること（シーディング要件の ID 6 直打ちの確認に対応）。
     */
    public function test_other_user_cannot_open_edit(): void
    {
        $this->actingAs($this->other)
            ->get(route('reading-plans.edit', $this->plan))
            ->assertForbidden();
    }

    /**
     * 他人の計画の更新は 403 で、期日が変わらないこと。
     */
    public function test_other_user_cannot_update(): void
    {
        $this->actingAs($this->other)
            ->put(route('reading-plans.update', $this->plan), ['target_date' => now()->addWeek()->toDateString()])
            ->assertForbidden();

        $this->assertDatabaseHas('reading_plans', ['id' => $this->plan->id, 'target_date' => '2026-12-01']);
    }

    /**
     * 他人の計画の読了は 403 で、状態が変わらないこと。
     */
    public function test_other_user_cannot_complete(): void
    {
        $this->actingAs($this->other)
            ->post(route('reading-plans.complete', $this->plan))
            ->assertForbidden();

        $this->assertDatabaseHas('reading_plans', ['id' => $this->plan->id, 'status' => ReadingPlanStatus::InProgress->value]);
    }

    /**
     * 他人の計画の削除は 403 で、計画が残ること。
     */
    public function test_other_user_cannot_delete(): void
    {
        $this->actingAs($this->other)
            ->delete(route('reading-plans.destroy', $this->plan))
            ->assertForbidden();

        $this->assertDatabaseHas('reading_plans', ['id' => $this->plan->id]);
    }

    // ---- 完了済み ----

    /**
     * 完了済みの計画は所有者でも編集画面・更新が 403 になること（ReadingPlanPolicy::update）。
     */
    public function test_completed_plan_cannot_be_edited(): void
    {
        $completed = ReadingPlan::factory()->for($this->owner)->completed()->create();

        $this->actingAs($this->owner)
            ->get(route('reading-plans.edit', $completed))
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->put(route('reading-plans.update', $completed), ['target_date' => now()->addWeek()->toDateString()])
            ->assertForbidden();
    }

    /**
     * 完了済みの計画を再度読了にしようとすると 403 になること（ReadingPlanPolicy::complete）。
     */
    public function test_completed_plan_cannot_be_completed_again(): void
    {
        $completed = ReadingPlan::factory()->for($this->owner)->completed()->create();
        $completedAt = $completed->completed_at;

        $this->actingAs($this->owner)
            ->post(route('reading-plans.complete', $completed))
            ->assertForbidden();

        $this->assertTrue($completed->refresh()->completed_at->equalTo($completedAt));
    }
}

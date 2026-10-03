<?php

namespace Tests\Feature\ReadingPlan;

use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ★ 読書計画の削除（DELETE /reading-plans/{readingPlan}）。所有者本人なら状態を問わず削除でき、
 * 一覧へ戻って「読書計画を削除しました。」が表示される（PM 回答 Q-006-2）。認可の検証は ReadingPlanAuthorizationTest。
 */
class ReadingPlanDestroyTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
    }

    /**
     * 進行中の計画を削除でき、一覧へリダイレクトされて成功メッセージが入ること。
     */
    public function test_plan_is_deleted(): void
    {
        $plan = ReadingPlan::factory()->for($this->owner)->create();

        $this->actingAs($this->owner)
            ->delete(route('reading-plans.destroy', $plan))
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success', '読書計画を削除しました。');

        $this->assertDatabaseMissing('reading_plans', ['id' => $plan->id]);
    }

    /**
     * 完了済みの計画も削除できること（Blade は全状態に「削除」を表示する）。
     */
    public function test_completed_plan_can_be_deleted(): void
    {
        $plan = ReadingPlan::factory()->for($this->owner)->completed()->create();

        $this->actingAs($this->owner)
            ->delete(route('reading-plans.destroy', $plan))
            ->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', ['id' => $plan->id]);
    }

    /**
     * 成功メッセージが一覧画面に描画されること。
     */
    public function test_success_message_is_visible_on_index(): void
    {
        $plan = ReadingPlan::factory()->for($this->owner)->create();

        $this->actingAs($this->owner)
            ->followingRedirects()
            ->delete(route('reading-plans.destroy', $plan))
            ->assertOk()
            ->assertSee('読書計画を削除しました。');
    }

    /**
     * 存在しない ID を指定すると 404 になること（PM 回答 Q-003-1: ID を指定する全画面で同様）。
     */
    public function test_unknown_plan_returns404(): void
    {
        $this->actingAs($this->owner)
            ->delete(route('reading-plans.destroy', 999))
            ->assertNotFound();
    }
}

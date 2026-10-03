<?php

namespace Tests\Feature\ReadingPlan;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ★PG15 読書計画一覧。認証必須で、ログインユーザー自身の計画だけを期日の近い順に表示し、
 * 状態で絞り込める（ReadingPlanIndexRequest）。各行の操作ボタンは Blade が状態で出し分ける。
 */
class ReadingPlanIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * 未認証でアクセスするとログイン画面へリダイレクトされること。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reading-plans.index'))
            ->assertRedirect(route('login'));
    }

    /**
     * 自分の計画だけが表示され、他人の計画は表示されないこと（whereBelongsTo の検証）。
     */
    public function test_only_own_plans_are_displayed(): void
    {
        ReadingPlan::factory()->for($this->user)->for(Book::factory()->create(['title' => '自分の本']))->create();
        ReadingPlan::factory()->for(Book::factory()->create(['title' => '他人の本']))->create();

        $this->actingAs($this->user)
            ->get(route('reading-plans.index'))
            ->assertOk()
            ->assertSee('自分の本')
            ->assertDontSee('他人の本');
    }

    /**
     * 各行に書籍名・期日・状態の表示名が描画されること。
     */
    public function test_plan_details_are_displayed(): void
    {
        ReadingPlan::factory()->for($this->user)
            ->for(Book::factory()->create(['title' => 'リーダブルコード']))
            ->create(['target_date' => '2026-12-24']);

        $this->actingAs($this->user)
            ->get(route('reading-plans.index'))
            ->assertOk()
            ->assertSee('リーダブルコード')
            ->assertSee('2026-12-24')
            ->assertSee('進行中');
    }

    /**
     * 期日の近い順に並ぶこと。
     */
    public function test_plans_are_ordered_by_target_date(): void
    {
        ReadingPlan::factory()->for($this->user)->for(Book::factory()->create(['title' => '来月の本']))->create(['target_date' => now()->addMonth()->toDateString()]);
        ReadingPlan::factory()->for($this->user)->for(Book::factory()->create(['title' => '明日の本']))->create(['target_date' => now()->addDay()->toDateString()]);

        $this->actingAs($this->user)
            ->get(route('reading-plans.index'))
            ->assertOk()
            ->assertSeeInOrder(['明日の本', '来月の本']);
    }

    /**
     * status で絞り込むと、その状態の計画だけが表示されること。
     */
    public function test_plans_can_be_filtered_by_status(): void
    {
        ReadingPlan::factory()->for($this->user)->for(Book::factory()->create(['title' => '進行中の本']))->create();
        ReadingPlan::factory()->for($this->user)->for(Book::factory()->create(['title' => '完了した本']))->completed()->create();
        ReadingPlan::factory()->for($this->user)->for(Book::factory()->create(['title' => '期限切れの本']))->expired()->create();

        $this->actingAs($this->user)
            ->get(route('reading-plans.index').'?status=completed')
            ->assertOk()
            ->assertSee('完了した本')
            ->assertDontSee('進行中の本')
            ->assertDontSee('期限切れの本');
    }

    /**
     * 不正な status 値は絞り込みなしの一覧へリダイレクトされること（URL 直打ち対策）。
     */
    public function test_invalid_status_redirects_to_index(): void
    {
        $this->actingAs($this->user)
            ->get(route('reading-plans.index').'?status=unknown')
            ->assertRedirect(route('reading-plans.index'));
    }

    /**
     * 進行中の計画には「読了する」「編集」「削除」が表示されること。
     */
    public function test_in_progress_plan_shows_all_actions(): void
    {
        $plan = ReadingPlan::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->get(route('reading-plans.index'))
            ->assertOk()
            ->assertSee(route('reading-plans.complete', $plan))
            ->assertSee(route('reading-plans.edit', $plan))
            ->assertSee(route('reading-plans.destroy', $plan));
    }

    /**
     * 完了済みの計画には「読了する」「編集」が表示されず、「削除」だけ表示されること。
     */
    public function test_completed_plan_shows_only_delete(): void
    {
        $plan = ReadingPlan::factory()->for($this->user)->completed()->create();

        $this->actingAs($this->user)
            ->get(route('reading-plans.index'))
            ->assertOk()
            ->assertDontSee(route('reading-plans.complete', $plan))
            ->assertDontSee(route('reading-plans.edit', $plan))
            ->assertSee(route('reading-plans.destroy', $plan))
            ->assertSee('完了');
    }

    /**
     * 計画が 1 件も無いとき、空状態の文言が表示されること。
     */
    public function test_empty_message_is_displayed(): void
    {
        $this->actingAs($this->user)
            ->get(route('reading-plans.index'))
            ->assertOk()
            ->assertSee('該当する読書計画はありません。');
    }
}

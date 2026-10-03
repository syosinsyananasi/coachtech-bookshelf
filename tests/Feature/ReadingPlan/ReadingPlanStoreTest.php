<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ★PG16 読書計画作成。認証必須で、書籍と期日を入力して登録すると進行中の計画が作られ、
 * 一覧へ戻って「読書計画を作成しました。」が表示される（PM 回答 Q-007-1）。
 * バリデーションは StoreReadingPlanRequest のルールとメッセージ（PM 回答）に 1対1 で対応させる。
 */
class ReadingPlanStoreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->book = Book::factory()->create(['title' => 'リーダブルコード', 'author' => 'Dustin Boswell']);
    }

    /**
     * バリデーションを通過する入力値を返す。
     *
     * @param  array<string, mixed>  $overrides  上書きしたい項目
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'book_id' => $this->book->id,
            'target_date' => now()->addWeek()->toDateString(),
        ], $overrides);
    }

    // ---- 画面表示 ----

    /**
     * 未認証で作成画面・登録を呼ぶとログイン画面へリダイレクトされること。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reading-plans.create'))->assertRedirect(route('login'));
        $this->post(route('reading-plans.store'), $this->validData())->assertRedirect(route('login'));

        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * 作成画面に書籍のプルダウン（タイトルと著者）と期日の入力欄が表示されること。
     */
    public function test_create_screen_shows_books_and_target_date(): void
    {
        $this->actingAs($this->user)
            ->get(route('reading-plans.create'))
            ->assertOk()
            ->assertSee('新規読書計画作成')
            ->assertSee('リーダブルコード（Dustin Boswell）')
            ->assertSee('name="target_date"', false);
    }

    // ---- 登録 ----

    /**
     * 正常な入力で計画が作られ、一覧へリダイレクトされて成功メッセージが入ること。
     */
    public function test_plan_is_created_and_redirects_to_index(): void
    {
        $this->actingAs($this->user)
            ->post(route('reading-plans.store'), $this->validData())
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success', '読書計画を作成しました。');

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'target_date' => now()->addWeek()->toDateString(),
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
    }

    /**
     * 成功メッセージが一覧画面に描画されること。
     */
    public function test_success_message_is_visible_on_index(): void
    {
        $this->actingAs($this->user)
            ->followingRedirects()
            ->post(route('reading-plans.store'), $this->validData())
            ->assertOk()
            ->assertSee('読書計画を作成しました。')
            ->assertSee('リーダブルコード');
    }

    /**
     * 期日は当日でも登録できること（after_or_equal:today）。
     */
    public function test_target_date_can_be_today(): void
    {
        $this->actingAs($this->user)
            ->post(route('reading-plans.store'), $this->validData(['target_date' => now()->toDateString()]))
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('reading_plans', 1);
    }

    // ---- バリデーション ----

    /**
     * 書籍が未選択のとき required エラーになること。
     */
    public function test_book_is_required(): void
    {
        $this->actingAs($this->user)
            ->post(route('reading-plans.store'), $this->validData(['book_id' => '']))
            ->assertSessionHasErrors(['book_id' => '書籍を選択してください。']);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * 存在しない書籍のとき exists エラーになること。
     */
    public function test_book_must_exist(): void
    {
        $this->actingAs($this->user)
            ->post(route('reading-plans.store'), $this->validData(['book_id' => 999]))
            ->assertSessionHasErrors(['book_id' => '選択された書籍は存在しません。']);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * 期日が未入力のとき required エラーになること。
     */
    public function test_target_date_is_required(): void
    {
        $this->actingAs($this->user)
            ->post(route('reading-plans.store'), $this->validData(['target_date' => '']))
            ->assertSessionHasErrors(['target_date' => '期日は必須です。']);
    }

    /**
     * 期日が日付でないとき date エラーになること。
     */
    public function test_target_date_must_be_valid_date(): void
    {
        $this->actingAs($this->user)
            ->post(route('reading-plans.store'), $this->validData(['target_date' => 'not-a-date']))
            ->assertSessionHasErrors(['target_date' => '期日は有効な日付形式で入力してください。']);
    }

    /**
     * 期日が過去日のとき after_or_equal エラーになること。
     */
    public function test_target_date_cannot_be_in_the_past(): void
    {
        $this->actingAs($this->user)
            ->post(route('reading-plans.store'), $this->validData(['target_date' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors(['target_date' => '期日は今日以降の日付を指定してください。']);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    // ---- 重複制御 ----

    /**
     * 同じ書籍で進行中の計画が既にあるとき unique エラーになること（PM 回答の重複制御）。
     */
    public function test_duplicate_in_progress_plan_for_same_book_is_rejected(): void
    {
        ReadingPlan::factory()->for($this->user)->for($this->book)->create();

        $this->actingAs($this->user)
            ->post(route('reading-plans.store'), $this->validData())
            ->assertSessionHasErrors(['book_id' => 'この書籍は既に進行中の読書計画が存在します。']);

        $this->assertDatabaseCount('reading_plans', 1);
    }

    /**
     * 同じ書籍でも完了済み・期限切れの計画しか無ければ再計画できること（複合 UNIQUE を張らない理由）。
     */
    public function test_same_book_can_be_planned_again_after_completion(): void
    {
        ReadingPlan::factory()->for($this->user)->for($this->book)->completed()->create();
        ReadingPlan::factory()->for($this->user)->for($this->book)->expired()->create();

        $this->actingAs($this->user)
            ->post(route('reading-plans.store'), $this->validData())
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('reading_plans', 3);
    }

    /**
     * 他ユーザーが同じ書籍を進行中でも、自分は計画できること（unique は user_id で絞られる）。
     */
    public function test_other_users_plan_does_not_block(): void
    {
        ReadingPlan::factory()->for($this->book)->create();

        $this->actingAs($this->user)
            ->post(route('reading-plans.store'), $this->validData())
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('reading_plans', 2);
    }

    /**
     * バリデーション失敗時、作成フォームにエラーメッセージが実際に描画されること。
     */
    public function test_validation_error_is_visible_on_screen(): void
    {
        $this->actingAs($this->user)
            ->from(route('reading-plans.create'))
            ->followingRedirects()
            ->post(route('reading-plans.store'), $this->validData(['book_id' => '']))
            ->assertOk()
            ->assertSee('書籍を選択してください。');
    }
}

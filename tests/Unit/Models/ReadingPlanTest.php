<?php

namespace Tests\Unit\Models;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * ReadingPlan モデル（★応用）。計画者・対象書籍（多対1）のリレーションと、
 * status の Enum キャスト、target_date / completed_at の日付キャスト、ファクトリの状態を検証する。
 */
class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * user() は BelongsTo で、計画者を返すこと。
     */
    public function test_user_returns_planner(): void
    {
        $planner = User::factory()->create();
        $plan = ReadingPlan::factory()->create(['user_id' => $planner->id]);

        $this->assertInstanceOf(BelongsTo::class, $plan->user());
        $this->assertTrue($plan->user->is($planner));
    }

    /**
     * book() は BelongsTo で、対象の書籍を返すこと。
     */
    public function test_book_returns_planned_book(): void
    {
        $book = Book::factory()->create();
        $plan = ReadingPlan::factory()->create(['book_id' => $book->id]);

        $this->assertInstanceOf(BelongsTo::class, $plan->book());
        $this->assertTrue($plan->book->is($book));
    }

    /**
     * status は ReadingPlanStatus の Enum としてキャストされ、DB には文字列で保存されること。
     */
    public function test_status_is_cast_to_enum(): void
    {
        $plan = ReadingPlan::factory()->create(['status' => ReadingPlanStatus::Expired]);

        $this->assertSame(ReadingPlanStatus::Expired, $plan->fresh()->status);
        $this->assertDatabaseHas('reading_plans', ['id' => $plan->id, 'status' => 'expired']);
    }

    /**
     * 新規作成時の status は DB の既定値により in_progress になること（PM 回答 Q-022）。
     */
    public function test_status_defaults_to_in_progress(): void
    {
        $plan = User::factory()->create()->readingPlans()->create([
            'book_id' => Book::factory()->create()->id,
            'target_date' => '2026-12-31',
        ]);

        $this->assertSame(ReadingPlanStatus::InProgress, $plan->fresh()->status);
    }

    /**
     * target_date は date、completed_at は datetime として Carbon で取り出せること。
     */
    public function test_dates_are_cast_to_carbon(): void
    {
        $plan = ReadingPlan::factory()->completed()->create(['target_date' => '2026-10-01']);

        $this->assertInstanceOf(Carbon::class, $plan->fresh()->target_date);
        $this->assertSame('2026-10-01', $plan->fresh()->target_date->format('Y-m-d'));
        $this->assertInstanceOf(Carbon::class, $plan->fresh()->completed_at);
    }

    /**
     * ファクトリの completed() は完了状態と読了日時を、expired() は期限切れ状態を設定すること。
     */
    public function test_factory_states(): void
    {
        $completed = ReadingPlan::factory()->completed()->create();
        $expired = ReadingPlan::factory()->expired()->create();

        $this->assertSame(ReadingPlanStatus::Completed, $completed->status);
        $this->assertNotNull($completed->completed_at);
        $this->assertSame(ReadingPlanStatus::Expired, $expired->status);
        $this->assertNull($expired->completed_at);
    }
}

<?php

namespace Tests\Feature\Report;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ★PG14 マイ読書レポート（ReadingReportService）。認証必須で、ログインユーザー自身のレビューと読書計画から
 * 基本サマリー・評価分布・高評価書籍 TOP5・ジャンル別評価傾向 TOP5 を集計する。
 * 読了冊数は完了した計画の書籍の種類数、TOP5 は書籍ごとに最高評価 1 件（PM 回答 Q-021）。
 */
class ReportIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * ログインユーザーのレビューを 1 件作る。
     */
    private function review(Book $book, int $rating, ?string $createdAt = null): Review
    {
        return Review::factory()->create([
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'rating' => $rating,
            'created_at' => $createdAt ?? now(),
        ]);
    }

    // ---- 表示 ----

    /**
     * 未認証でアクセスするとログイン画面へリダイレクトされること。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reports.index'))
            ->assertRedirect(route('login'));
    }

    /**
     * 4 区画の見出しが表示されること。
     */
    public function test_report_screen_is_displayed(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('基本統計')
            ->assertSee('評価分布')
            ->assertSee('高評価書籍 TOP5')
            ->assertSee('ジャンル別評価傾向 TOP5');
    }

    // ---- 基本サマリー ----

    /**
     * 総レビュー数・平均評価は自分のレビューだけから計算され、平均は小数第 1 位で表示されること。
     */
    public function test_summary_counts_only_own_reviews(): void
    {
        $this->review(Book::factory()->create(), 5);
        $this->review(Book::factory()->create(), 4);
        Review::factory()->create(['rating' => 1]); // 他人のレビュー

        $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['summary']['total_reviews'] === 2 && $stats['summary']['average_rating'] === 4.5)
            ->assertSee('4.5');
    }

    /**
     * レポートの読了冊数を返す。
     */
    private function booksRead(): int
    {
        return $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertOk()
            ->viewData('stats')['summary']['books_read'];
    }

    /**
     * 読了冊数は完了した読書計画の書籍の種類数で、同じ書籍を 2 回完了しても 1 冊と数えること（distinct の検証）。
     */
    public function test_books_read_counts_same_book_once(): void
    {
        $book = Book::factory()->create();
        ReadingPlan::factory()->for($this->user)->for($book)->completed()->create();
        ReadingPlan::factory()->for($this->user)->for($book)->completed()->create();

        $this->assertSame(1, $this->booksRead());
    }

    /**
     * 進行中の計画は読了冊数に数えないこと（status の条件の検証）。
     */
    public function test_books_read_excludes_in_progress_plans(): void
    {
        ReadingPlan::factory()->for($this->user)->completed()->create();
        ReadingPlan::factory()->for($this->user)->create();

        $this->assertSame(1, $this->booksRead());
    }

    /**
     * 他ユーザーの完了した計画は読了冊数に数えないこと（ログインユーザーへの絞り込みの検証）。
     */
    public function test_books_read_excludes_other_users_plans(): void
    {
        ReadingPlan::factory()->for($this->user)->completed()->create();
        ReadingPlan::factory()->completed()->create();

        $this->assertSame(1, $this->booksRead());
    }

    /**
     * レビューが無いとき平均評価は「-」で表示されること。
     */
    public function test_average_shows_dash_when_no_reviews(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['summary']['average_rating'] === 0.0)
            ->assertSee('-');
    }

    // ---- 評価分布 ----

    /**
     * 1〜5 星ごとの件数が添字 0〜4 の順で集計されること。
     */
    public function test_rating_distribution_counts_each_star(): void
    {
        $this->review(Book::factory()->create(), 5);
        $this->review(Book::factory()->create(), 5);
        $this->review(Book::factory()->create(), 3);

        $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['rating_distribution']->all() === [0, 0, 1, 0, 2])
            ->assertSee('2件');
    }

    // ---- 高評価書籍 TOP5 ----

    /**
     * 4 星以上の書籍だけが評価の高い順に並び、3 星以下は含まれないこと。
     */
    public function test_top_rated_books_include_only_four_stars_and_above(): void
    {
        $this->review(Book::factory()->create(['title' => '4星の本']), 4);
        $this->review(Book::factory()->create(['title' => '5星の本']), 5);
        $this->review(Book::factory()->create(['title' => '3星の本']), 3);

        $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('stats', fn ($stats) => array_column($stats['top_rated_books'], 'title') === ['5星の本', '4星の本'])
            ->assertDontSee('3星の本');
    }

    /**
     * 同じ書籍に複数のレビューがあるとき 1 件にまとめ、最も高い評価を採用すること（PM 回答 Q-021）。
     */
    public function test_top_rated_books_merge_same_book_with_highest_rating(): void
    {
        $book = Book::factory()->create(['title' => 'リーダブルコード']);
        $this->review($book, 4, now()->subDay()->toDateTimeString());
        $this->review($book, 5);

        $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('stats', fn ($stats) => count($stats['top_rated_books']) === 1 && $stats['top_rated_books'][0]['rating'] === 5);
    }

    /**
     * 最大 5 件に制限されること。
     */
    public function test_top_rated_books_are_limited_to_five(): void
    {
        Book::factory()->count(6)->create()->each(fn (Book $book) => $this->review($book, 5));

        $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('stats', fn ($stats) => count($stats['top_rated_books']) === 5);
    }

    /**
     * 4 星以上が無いとき、空状態の文言が表示されること。
     */
    public function test_top_rated_books_empty_message(): void
    {
        $this->review(Book::factory()->create(), 3);

        $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('4星以上の書籍がありません');
    }

    // ---- ジャンル別評価傾向 TOP5 ----

    /**
     * ジャンルごとの平均評価と件数が集計され、平均の高い順に並ぶこと。複数ジャンルの書籍はそれぞれに数える。
     */
    public function test_genre_ratings_are_averaged_per_genre(): void
    {
        $novel = Genre::factory()->create(['name' => '小説']);
        $tech = Genre::factory()->create(['name' => '技術書']);
        $both = Book::factory()->create();
        $both->genres()->attach([$novel->id, $tech->id]);
        $techOnly = Book::factory()->create();
        $techOnly->genres()->attach($tech);

        $this->review($both, 5);
        $this->review($techOnly, 3);

        $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['genre_ratings'] === [
                ['id' => $novel->id, 'name' => '小説', 'count' => 1, 'average_rating' => 5.0],
                ['id' => $tech->id, 'name' => '技術書', 'count' => 2, 'average_rating' => 4.0],
            ])
            ->assertSee('2件のレビュー');
    }

    /**
     * ログインユーザーのレビューが 1 件も無いとき、ジャンル別評価傾向に空状態の文言が表示されること。
     */
    public function test_genre_ratings_empty_message_when_no_reviews(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('ジャンルが設定された書籍のレビューがありません');
    }
}

<?php

namespace Tests\Feature\Ranking;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PG11 ランキング。レビュー平均評価の降順で最大 10件、レビューが無い書籍は除外されることを重点的に検証する。
 * 公開ページのためゲストでも閲覧できること。
 */
class RankingIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 指定タイトルの書籍を作り、指定した評価のレビューを付ける。
     *
     * @param  array<int, int>  $ratings  付けるレビューの評価値
     */
    private function bookWithRatings(string $title, array $ratings): Book
    {
        $book = Book::factory()->create(['title' => $title]);
        collect($ratings)->each(fn (int $rating) => Review::factory()->create(['book_id' => $book->id, 'rating' => $rating]));

        return $book;
    }

    /**
     * ゲストでもランキングを表示できること。
     */
    public function test_ranking_is_accessible_by_guest(): void
    {
        $this->bookWithRatings('リーダブルコード', [5]);

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertSee('評価ランキング TOP 10')
            ->assertSee('リーダブルコード');
    }

    /**
     * 平均評価の高い順に並ぶこと（withAvg + orderByDesc の検証）。
     */
    public function test_books_are_ordered_by_average_rating(): void
    {
        $this->bookWithRatings('平均3の書籍', [3, 3]);
        $this->bookWithRatings('平均5の書籍', [5, 5]);
        $this->bookWithRatings('平均4の書籍', [3, 5]);

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertSeeInOrder(['平均5の書籍', '平均4の書籍', '平均3の書籍']);
    }

    /**
     * 平均評価と件数が描画されること（reviews_avg_rating / reviews_count の検証）。
     */
    public function test_average_and_count_are_displayed(): void
    {
        $this->bookWithRatings('リーダブルコード', [4, 5]);

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertSee('4.50')
            ->assertSee('(2件のレビュー)');
    }

    /**
     * レビューが無い書籍は表示されないこと（has('reviews') の検証）。
     */
    public function test_books_without_reviews_are_not_displayed(): void
    {
        $this->bookWithRatings('レビューあり', [4]);
        Book::factory()->create(['title' => 'レビューなし']);

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertSee('レビューあり')
            ->assertDontSee('レビューなし');
    }

    /**
     * 11冊にレビューがあっても上位 10件 だけ表示され、11位は表示されないこと（take(10) の検証）。
     */
    public function test_only_top_ten_are_displayed(): void
    {
        collect(range(1, 10))->each(fn (int $rank) => $this->bookWithRatings('上位の書籍'.$rank, [5]));
        $this->bookWithRatings('11位の書籍', [1]);

        $content = $this->get(route('ranking.index'))
            ->assertOk()
            ->assertDontSee('11位の書籍')
            ->getContent();

        $this->assertSame(10, preg_match_all('#href="'.preg_quote(url('/books'), '#').'/\d+"#', $content));
    }

    /**
     * レビューが 1件 も無いとき空メッセージが表示されること。
     */
    public function test_empty_message_is_displayed_when_no_reviews(): void
    {
        Book::factory()->create();

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertSee('まだレビューが投稿された書籍がありません。');
    }
}

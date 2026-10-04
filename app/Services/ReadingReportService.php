<?php

namespace App\Services;

use App\Enums\ReadingPlanStatus;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * マイ読書レポート（★PG14）の集計。
 *
 * ログインユーザー自身のレビューと読書計画から 4 区画の統計を組み立てる。
 * 規約（CLAUDE.md §6）に従い、foreach ではなく Collection メソッドで宣言的に書く。
 * 返す配列のキーと形は reports/index.blade.php が参照するものに合わせる。
 */
class ReadingReportService
{
    /**
     * レポートの全区画を組み立てる。
     *
     * @param  User  $user  ログインユーザー
     * @return array{summary: array{total_reviews: int, books_read: int, average_rating: float}, rating_distribution: Collection<int, int>, top_rated_books: array<int, array{id: int, title: string, author: string, rating: int}>, genre_ratings: array<int, array{id: int, name: string, count: int, average_rating: float}>}
     */
    public function build(User $user): array
    {
        $reviews = $user->reviews()->with('book.genres')->latest()->get();

        return [
            'summary' => $this->summary($user, $reviews),
            'rating_distribution' => $this->ratingDistribution($reviews),
            'top_rated_books' => $this->topRatedBooks($reviews),
            'genre_ratings' => $this->genreRatings($reviews),
        ];
    }

    /**
     * 基本サマリー: 総レビュー数・読了冊数・平均評価点。
     *
     * 読了冊数は「完了になった読書計画の書籍の種類数」（同じ書籍を複数回完了しても 1 冊。レビューは判定に使わない）。
     * 平均評価はレビューが無ければ 0（Blade が「-」を表示する）。
     *
     * @param  Collection<int, Review>  $reviews
     * @return array{total_reviews: int, books_read: int, average_rating: float}
     */
    private function summary(User $user, Collection $reviews): array
    {
        $booksRead = $user->readingPlans()
            ->where('status', ReadingPlanStatus::Completed->value)
            ->distinct()
            ->count('book_id');

        return [
            'total_reviews' => $reviews->count(),
            'books_read' => $booksRead,
            'average_rating' => (float) $reviews->avg('rating'),
        ];
    }

    /**
     * 評価分布: 1〜5 星それぞれの件数。
     *
     * Blade は添字 0〜4 を星 1〜5 として描画し、max() で横バーの比率を出すため Collection で返す。
     *
     * @param  Collection<int, Review>  $reviews
     * @return Collection<int, int> 添字 0 が 1 星、添字 4 が 5 星の件数
     */
    private function ratingDistribution(Collection $reviews): Collection
    {
        $countsByRating = $reviews->countBy('rating');

        return collect(range(1, 5))->map(fn (int $rating) => $countsByRating->get($rating, 0));
    }

    /**
     * 高評価書籍 TOP5: 4 星以上の書籍を評価の高い順に最大 5 件。
     *
     * 同じ書籍に複数のレビューがある場合は 1 件にまとめ、最も高い評価を採用する（PM 回答 Q-021）。
     * 評価が同じなら新しいレビューを先にする。
     *
     * @param  Collection<int, Review>  $reviews  新しい順に並んだレビュー
     * @return array<int, array{id: int, title: string, author: string, rating: int}>
     */
    private function topRatedBooks(Collection $reviews): array
    {
        return $reviews
            ->where('rating', '>=', 4)
            ->sortBy([['rating', 'desc'], ['created_at', 'desc']])
            ->unique('book_id')
            ->take(5)
            ->map(fn (Review $review) => [
                'id' => $review->book->id,
                'title' => $review->book->title,
                'author' => $review->book->author,
                'rating' => $review->rating,
            ])
            ->values()
            ->all();
    }

    /**
     * ジャンル別評価傾向 TOP5: ジャンルごとの平均評価と件数を高い順に最大 5 件。
     *
     * 1 冊が複数ジャンルに属する場合はそれぞれのジャンルに数える。
     * そのユーザーのレビューが 1 件も無いときは空になる（書籍には必ず 1 つ以上のジャンルが付くため、ジャンル無しで空になることはない）。
     * 平均が同じなら件数の多いジャンルを先にする。
     *
     * @param  Collection<int, Review>  $reviews
     * @return array<int, array{id: int, name: string, count: int, average_rating: float}>
     */
    private function genreRatings(Collection $reviews): array
    {
        return $reviews
            ->flatMap(fn (Review $review) => $this->genreRatingPairs($review))
            ->groupBy('id')
            ->map(fn (Collection $pairs) => [
                'id' => $pairs->first()['id'],
                'name' => $pairs->first()['name'],
                'count' => $pairs->count(),
                'average_rating' => (float) $pairs->avg('rating'),
            ])
            ->sortBy([['average_rating', 'desc'], ['count', 'desc']])
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * 1 件のレビューを「ジャンル × 評価」の組に展開する。
     *
     * @return Collection<int, array{id: int, name: string, rating: int}> 書籍のジャンル数ぶんの組
     */
    private function genreRatingPairs(Review $review): Collection
    {
        return $review->book->genres->map(fn (Genre $genre) => [
            'id' => $genre->id,
            'name' => $genre->name,
            'rating' => $review->rating,
        ]);
    }
}

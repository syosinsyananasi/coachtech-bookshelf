<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Review モデル。投稿者・対象書籍（多対1）と、いいねしたユーザー（中間テーブル review_likes の多対多）のリレーションを検証する。
 */
class ReviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * user() は BelongsTo で、投稿者を返すこと。
     */
    public function test_user_returns_author(): void
    {
        $author = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $author->id]);

        $this->assertInstanceOf(BelongsTo::class, $review->user());
        $this->assertTrue($review->user->is($author));
    }

    /**
     * book() は BelongsTo で、レビュー対象の書籍を返すこと。
     */
    public function test_book_returns_reviewed_book(): void
    {
        $book = Book::factory()->create();
        $review = Review::factory()->create(['book_id' => $book->id]);

        $this->assertInstanceOf(BelongsTo::class, $review->book());
        $this->assertTrue($review->book->is($book));
    }

    /**
     * likedByUsers() は中間テーブル review_likes を介した BelongsToMany で、いいねしたユーザーを返すこと。
     */
    public function test_liked_by_users_uses_review_likes_pivot(): void
    {
        $review = Review::factory()->create();
        $likers = User::factory()->count(2)->create();
        $review->likedByUsers()->attach($likers->pluck('id'));

        $this->assertInstanceOf(BelongsToMany::class, $review->likedByUsers());
        $this->assertSame('review_likes', $review->likedByUsers()->getTable());
        $this->assertCount(2, $review->likedByUsers);
    }

    /**
     * 同じユーザーが同じレビューに 2 回いいねしても、複合一意制約により 1 行のままであること（syncWithoutDetaching）。
     */
    public function test_same_user_cannot_like_twice(): void
    {
        $review = Review::factory()->create();
        $liker = User::factory()->create();
        $review->likedByUsers()->syncWithoutDetaching([$liker->id]);
        $review->likedByUsers()->syncWithoutDetaching([$liker->id]);

        $this->assertCount(1, $review->fresh()->likedByUsers);
    }
}

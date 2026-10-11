<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * User モデル。所有する書籍・レビュー・読書計画（1対多）と、
 * いいね・お気に入り（中間テーブルを介した多対多）のリレーション、パスワードのハッシュ化キャストを検証する。
 */
class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * books() は HasMany で、自分が登録した書籍だけを返すこと。
     */
    public function test_books_returns_only_owned_books(): void
    {
        $user = User::factory()->create();
        $ownBook = Book::factory()->create(['user_id' => $user->id]);
        Book::factory()->create();

        $this->assertInstanceOf(HasMany::class, $user->books());
        $this->assertTrue($user->books->contains($ownBook));
        $this->assertCount(1, $user->books);
    }

    /**
     * reviews() は HasMany で、自分が投稿したレビューだけを返すこと。
     */
    public function test_reviews_returns_only_own_reviews(): void
    {
        $user = User::factory()->create();
        $ownReview = Review::factory()->create(['user_id' => $user->id]);
        Review::factory()->create();

        $this->assertInstanceOf(HasMany::class, $user->reviews());
        $this->assertTrue($user->reviews->contains($ownReview));
        $this->assertCount(1, $user->reviews);
    }

    /**
     * likedReviews() は中間テーブル review_likes を介した BelongsToMany で、いいねしたレビューを返すこと。
     */
    public function test_liked_reviews_uses_review_likes_pivot(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();
        $user->likedReviews()->attach($review);

        $this->assertInstanceOf(BelongsToMany::class, $user->likedReviews());
        $this->assertSame('review_likes', $user->likedReviews()->getTable());
        $this->assertTrue($user->likedReviews->contains($review));
    }

    /**
     * favoriteBooks() は中間テーブル favorites を介した BelongsToMany で、お気に入りの書籍を返すこと。
     */
    public function test_favorite_books_uses_favorites_pivot(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $user->favoriteBooks()->attach($book);

        $this->assertInstanceOf(BelongsToMany::class, $user->favoriteBooks());
        $this->assertSame('favorites', $user->favoriteBooks()->getTable());
        $this->assertTrue($user->favoriteBooks->contains($book));
    }

    /**
     * readingPlans() は HasMany で、自分の読書計画だけを返すこと（★応用）。
     */
    public function test_reading_plans_returns_only_own_plans(): void
    {
        $user = User::factory()->create();
        $ownPlan = ReadingPlan::factory()->create(['user_id' => $user->id]);
        ReadingPlan::factory()->create();

        $this->assertInstanceOf(HasMany::class, $user->readingPlans());
        $this->assertTrue($user->readingPlans->contains($ownPlan));
        $this->assertCount(1, $user->readingPlans);
    }

    /**
     * password は hashed キャストにより、平文で代入してもハッシュ化して保存されること。
     */
    public function test_password_is_hashed_on_save(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->assertNotSame('password', $user->password);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    /**
     * password と remember_token は $hidden により配列化・JSON 化のときに含まれないこと。
     */
    public function test_password_and_remember_token_are_hidden(): void
    {
        $user = User::factory()->create();

        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }
}

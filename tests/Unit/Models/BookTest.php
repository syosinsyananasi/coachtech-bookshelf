<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Book モデル。登録者（多対1）、ジャンル（中間テーブル book_genre の多対多）、
 * レビュー・読書計画（1対多）のリレーションと、出版日の date キャストを検証する。
 */
class BookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * user() は BelongsTo で、登録者のユーザーを返すこと。
     */
    public function test_user_returns_owner(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);

        $this->assertInstanceOf(BelongsTo::class, $book->user());
        $this->assertTrue($book->user->is($owner));
    }

    /**
     * genres() は中間テーブル book_genre を介した BelongsToMany で、紐付けたジャンルを返すこと。
     */
    public function test_genres_uses_book_genre_pivot(): void
    {
        $book = Book::factory()->create();
        $genres = Genre::factory()->count(2)->create();
        $book->genres()->sync($genres->pluck('id'));

        $this->assertInstanceOf(BelongsToMany::class, $book->genres());
        $this->assertSame('book_genre', $book->genres()->getTable());
        $this->assertCount(2, $book->genres);
    }

    /**
     * reviews() は HasMany で、この書籍へのレビューだけを返すこと。
     */
    public function test_reviews_returns_only_reviews_of_the_book(): void
    {
        $book = Book::factory()->create();
        $review = Review::factory()->create(['book_id' => $book->id]);
        Review::factory()->create();

        $this->assertInstanceOf(HasMany::class, $book->reviews());
        $this->assertTrue($book->reviews->contains($review));
        $this->assertCount(1, $book->reviews);
    }

    /**
     * readingPlans() は HasMany で、この書籍の読書計画だけを返すこと（★応用）。
     */
    public function test_reading_plans_returns_only_plans_of_the_book(): void
    {
        $book = Book::factory()->create();
        $plan = ReadingPlan::factory()->create(['book_id' => $book->id]);
        ReadingPlan::factory()->create();

        $this->assertInstanceOf(HasMany::class, $book->readingPlans());
        $this->assertTrue($book->readingPlans->contains($plan));
        $this->assertCount(1, $book->readingPlans);
    }

    /**
     * published_date は date キャストにより Carbon として取り出せること。
     */
    public function test_published_date_is_cast_to_carbon(): void
    {
        $book = Book::factory()->create(['published_date' => '2012-06-23']);

        $this->assertInstanceOf(Carbon::class, $book->fresh()->published_date);
        $this->assertSame('2012-06-23', $book->fresh()->published_date->format('Y-m-d'));
    }

    /**
     * ★応用で ISBN と出版日は任意入力のため、null のまま保存・取得できること。
     */
    public function test_isbn_and_published_date_can_be_null(): void
    {
        $book = Book::factory()->create(['isbn' => null, 'published_date' => null]);

        $this->assertNull($book->fresh()->isbn);
        $this->assertNull($book->fresh()->published_date);
    }

    /**
     * user_id は $fillable に含めていないため、複数代入では設定されないこと（登録者はリレーション経由で決める）。
     */
    public function test_user_id_is_not_mass_assignable(): void
    {
        $book = new Book(['title' => 'リーダブルコード', 'user_id' => 999]);

        $this->assertSame('リーダブルコード', $book->title);
        $this->assertNull($book->user_id);
    }
}

<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * レビュー削除。レビュー本体と中間テーブルのいいねが消え、書籍自体は残ること。
 * 認可（投稿者以外の拒否）は ReviewAuthorizationTest で扱う。
 */
class ReviewDestroyTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private Book $book;

    private Review $review;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create();
        $this->book = Book::factory()->create();
        $this->review = Review::factory()->create([
            'user_id' => $this->author->id,
            'book_id' => $this->book->id,
        ]);
    }

    /**
     * 投稿者本人が削除でき、書籍詳細へリダイレクトされて成功メッセージがセッションに入ること。
     */
    public function test_review_is_deleted_by_author(): void
    {
        $this->actingAs($this->author)
            ->delete(route('reviews.destroy', $this->review))
            ->assertRedirect(route('books.show', $this->book))
            ->assertSessionHas('success', 'レビューを削除しました。');

        $this->assertDatabaseMissing('reviews', ['id' => $this->review->id]);
    }

    /**
     * 削除後の書籍詳細に成功メッセージが実際に描画されること。
     */
    public function test_success_message_is_visible_on_screen(): void
    {
        $this->actingAs($this->author)
            ->followingRedirects()
            ->delete(route('reviews.destroy', $this->review))
            ->assertOk()
            ->assertSee('レビューを削除しました。');
    }

    /**
     * レビューを削除すると中間テーブルのいいねも消えること（外部キーの cascade の検証）。
     */
    public function test_like_rows_are_deleted_with_review(): void
    {
        $liker = User::factory()->create();
        $this->review->likedByUsers()->attach($liker);

        $this->actingAs($this->author)
            ->delete(route('reviews.destroy', $this->review));

        $this->assertDatabaseMissing('review_likes', ['review_id' => $this->review->id, 'user_id' => $liker->id]);
    }

    /**
     * レビューを削除しても書籍自体は残ること。
     */
    public function test_book_remains_after_review_is_deleted(): void
    {
        $this->actingAs($this->author)
            ->delete(route('reviews.destroy', $this->review));

        $this->assertDatabaseHas('books', ['id' => $this->book->id]);
    }
}

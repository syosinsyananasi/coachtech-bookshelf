<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AP05 書籍削除API。204 が返り書籍本体と関連データ（レビュー・お気に入り・ジャンル紐付け）が消えること、
 * 存在しない ID で 404 になることを検証する。
 */
class BookDestroyTest extends TestCase
{
    use RefreshDatabase;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        $this->book = Book::factory()->create();
    }

    /**
     * 削除すると 204（本文なし）が返り、書籍が消えること。
     */
    public function test_book_is_deleted_with204(): void
    {
        $this->deleteJson(route('api.v1.books.destroy', $this->book))
            ->assertNoContent();

        $this->assertDatabaseMissing('books', ['id' => $this->book->id]);
    }

    /**
     * 削除後に同じ ID を取得すると 404 になること。
     */
    public function test_deleted_book_is_not_found(): void
    {
        $this->deleteJson(route('api.v1.books.destroy', $this->book));

        $this->getJson(route('api.v1.books.show', $this->book->id))
            ->assertNotFound();
    }

    /**
     * 関連するレビュー・お気に入り・ジャンル紐付けも消えること（外部キーの cascade の検証）。
     */
    public function test_related_rows_are_deleted_with_book(): void
    {
        $genre = Genre::factory()->create();
        $user = User::factory()->create();
        $this->book->genres()->attach($genre);
        $review = Review::factory()->create(['book_id' => $this->book->id]);
        $user->favoriteBooks()->attach($this->book);

        $this->deleteJson(route('api.v1.books.destroy', $this->book))
            ->assertNoContent();

        $this->assertDatabaseMissing('book_genre', ['book_id' => $this->book->id]);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertDatabaseMissing('favorites', ['book_id' => $this->book->id]);
        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /**
     * 存在しない ID を指定すると 404 の JSON が返り、他の書籍は消えないこと。
     */
    public function test_not_found_for_missing_book(): void
    {
        $this->deleteJson(route('api.v1.books.destroy', 999))
            ->assertNotFound()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('books', ['id' => $this->book->id]);
    }
}

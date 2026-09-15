<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AP02 書籍詳細API。ジャンルとレビュー（投稿者名・評価・コメント・投稿日時）を含むこと、存在しない ID で 404 になることを検証する。
 */
class BookShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証なしで 200 が返り、書籍の各項目が含まれること。
     */
    public function test_show_returns_book_json(): void
    {
        $book = Book::factory()->create([
            'title' => 'リーダブルコード',
            'author' => 'Dustin Boswell',
            'isbn' => '9784873115658',
            'published_date' => '2012-06-23',
        ]);

        $this->getJson(route('api.v1.books.show', $book))
            ->assertOk()
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', 'リーダブルコード')
            ->assertJsonPath('data.author', 'Dustin Boswell')
            ->assertJsonPath('data.isbn', '9784873115658')
            ->assertJsonPath('data.published_date', '2012-06-23');
    }

    /**
     * ジャンルが id と name で含まれること。
     */
    public function test_show_includes_genres(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create(['name' => '技術書']);
        $book->genres()->attach($genre);

        $this->getJson(route('api.v1.books.show', $book))
            ->assertOk()
            ->assertJsonPath('data.genres.0.id', $genre->id)
            ->assertJsonPath('data.genres.0.name', '技術書');
    }

    /**
     * レビューに投稿者名・評価・コメント・投稿日時が含まれ、平均評価と件数も付くこと。
     */
    public function test_show_includes_reviews_with_user_name(): void
    {
        $book = Book::factory()->create();
        $user = User::factory()->create(['name' => '鈴木花子']);
        Review::factory()->create(['book_id' => $book->id, 'user_id' => $user->id, 'rating' => 4, 'comment' => '読みやすい']);
        Review::factory()->create(['book_id' => $book->id, 'rating' => 5]);

        $this->getJson(route('api.v1.books.show', $book))
            ->assertOk()
            ->assertJsonCount(2, 'data.reviews')
            ->assertJsonStructure(['data' => ['reviews' => [['id', 'user_name', 'rating', 'comment', 'created_at']]]])
            ->assertJsonFragment(['user_name' => '鈴木花子', 'rating' => 4, 'comment' => '読みやすい'])
            ->assertJsonPath('data.average_rating', 4.5)
            ->assertJsonPath('data.reviews_count', 2);
    }

    /**
     * 存在しない ID を指定すると 404 の JSON が返ること。
     */
    public function test_not_found_for_missing_book(): void
    {
        $this->getJson(route('api.v1.books.show', 999))
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }
}

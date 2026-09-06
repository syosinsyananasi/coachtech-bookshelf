<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍削除。書籍本体と中間テーブルの紐付けが消え、ジャンル自体は残ること。
 * 認可（作成者以外の拒否）は BookAuthorizationTest で扱う。
 */
class BookDestroyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 作成者本人が削除でき、一覧へリダイレクトされて成功メッセージがセッションに入ること。
     */
    public function test_book_is_deleted_by_owner(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->delete(route('books.destroy', $book))
            ->assertRedirect(route('books.index'))
            ->assertSessionHas('success', '書籍を削除しました。');

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    /**
     * 削除後の一覧画面に成功メッセージが実際に描画されること。
     */
    public function test_success_message_is_visible_on_screen(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->followingRedirects()
            ->delete(route('books.destroy', $book))
            ->assertOk()
            ->assertSee('書籍を削除しました。');
    }

    /**
     * 書籍を削除すると中間テーブルの紐付けも消えること（外部キーの cascade の検証）。
     */
    public function test_pivot_rows_are_deleted_with_book(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);
        $genre = Genre::factory()->create();
        $book->genres()->attach($genre);

        $this->actingAs($owner)
            ->delete(route('books.destroy', $book));

        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id, 'genre_id' => $genre->id]);
    }

    /**
     * 書籍を削除してもジャンル自体は残ること。
     */
    public function test_genre_remains_after_book_is_deleted(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);
        $genre = Genre::factory()->create();
        $book->genres()->attach($genre);

        $this->actingAs($owner)
            ->delete(route('books.destroy', $book));

        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }
}

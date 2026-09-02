<?php

namespace Tests\Feature\Genre;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ジャンル削除。書籍が紐づいている場合は削除を制限すること（機能要件）。
 */
class GenreDestroyTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    /**
     * 書籍が紐づいていないジャンルは削除できること。
     */
    public function test_genre_without_books_is_deleted(): void
    {
        $genre = Genre::factory()->create();

        $this->actingAs($this->user())
            ->delete(route('genres.destroy', $genre))
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('success', 'ジャンルを削除しました。');

        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }

    /**
     * 削除後の画面に成功メッセージが実際に描画されること。
     */
    public function test_success_message_is_visible_on_screen(): void
    {
        $genre = Genre::factory()->create();

        $this->actingAs($this->user())
            ->followingRedirects()
            ->delete(route('genres.destroy', $genre))
            ->assertOk()
            ->assertSee('ジャンルを削除しました。');
    }

    /**
     * 書籍が紐づくジャンルは削除されず、エラーメッセージが返ること（削除制限の要件）。
     */
    public function test_genre_with_books_is_not_deleted(): void
    {
        $genre = Genre::factory()->create();
        $genre->books()->attach(Book::factory()->create());

        $this->actingAs($this->user())
            ->delete(route('genres.destroy', $genre))
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('error', 'このジャンルには書籍が紐付いているため削除できません。');

        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }

    /**
     * 削除が拒否されたとき、中間テーブルの紐付けも残っていること。
     */
    public function test_pivot_row_remains_when_deletion_is_blocked(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();
        $genre->books()->attach($book);

        $this->actingAs($this->user())
            ->delete(route('genres.destroy', $genre));

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    /**
     * 削除拒否時の画面にエラーメッセージが実際に描画されること。
     */
    public function test_error_message_is_visible_on_screen(): void
    {
        $genre = Genre::factory()->create();
        $genre->books()->attach(Book::factory()->create());

        $this->actingAs($this->user())
            ->followingRedirects()
            ->delete(route('genres.destroy', $genre))
            ->assertOk()
            ->assertSee('このジャンルには書籍が紐付いているため削除できません。');
    }
}

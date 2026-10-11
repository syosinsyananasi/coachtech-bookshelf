<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Genre モデル。書籍（中間テーブル book_genre の多対多）のリレーションと、書籍数の集計を検証する。
 */
class GenreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * books() は中間テーブル book_genre を介した BelongsToMany で、紐付けた書籍を返すこと。
     */
    public function test_books_uses_book_genre_pivot(): void
    {
        $genre = Genre::factory()->create();
        $books = Book::factory()->count(2)->create();
        $genre->books()->sync($books->pluck('id'));

        $this->assertInstanceOf(BelongsToMany::class, $genre->books());
        $this->assertSame('book_genre', $genre->books()->getTable());
        $this->assertCount(2, $genre->books);
    }

    /**
     * 他のジャンルに紐付けた書籍は含まれず、withCount で正しい書籍数が取れること（ジャンル一覧の表示に使う）。
     */
    public function test_books_count_excludes_other_genres(): void
    {
        $genre = Genre::factory()->create();
        $otherGenre = Genre::factory()->create();
        $genre->books()->sync([Book::factory()->create()->id]);
        $otherGenre->books()->sync(Book::factory()->count(2)->create()->pluck('id'));

        $this->assertSame(1, Genre::withCount('books')->find($genre->id)->books_count);
    }
}

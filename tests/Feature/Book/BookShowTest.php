<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PG02 書籍詳細。公開ページとして書籍情報とジャンルが表示され、
 * 編集・削除ボタンは作成者本人にだけ表示されること。
 */
class BookShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストでも詳細を開けること（公開ページ）。
     */
    public function test_show_is_accessible_by_guest(): void
    {
        $book = Book::factory()->create();

        $this->get(route('books.show', $book))
            ->assertOk();
    }

    /**
     * タイトル・著者・ISBN・出版日・説明が表示されること。
     */
    public function test_book_details_are_displayed(): void
    {
        $book = Book::factory()->create([
            'title' => 'リーダブルコード',
            'author' => 'Dustin Boswell',
            'isbn' => '9784873115658',
            'published_date' => '2012-06-23',
            'description' => '読みやすいコードを書くための技術。',
        ]);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('リーダブルコード')
            ->assertSee('Dustin Boswell')
            ->assertSee('9784873115658')
            ->assertSee('2012-06-23')
            ->assertSee('読みやすいコードを書くための技術。');
    }

    /**
     * 紐づくジャンル名が表示されること。
     */
    public function test_genres_are_displayed(): void
    {
        $book = Book::factory()->create();
        $book->genres()->attach(Genre::factory()->create(['name' => '技術書']));

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('技術書');
    }

    /**
     * 作成者本人には編集・削除ボタンが表示されること（@can が BookPolicy を参照している検証）。
     */
    public function test_edit_and_delete_buttons_are_visible_to_owner(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee(route('books.edit', $book))
            ->assertSee('name="_method" value="DELETE"', false);
    }

    /**
     * 作成者以外のユーザーには編集・削除ボタンが表示されないこと。
     */
    public function test_edit_and_delete_buttons_are_hidden_from_other_user(): void
    {
        $book = Book::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee(route('books.edit', $book))
            ->assertDontSee('name="_method" value="DELETE"', false);
    }

    /**
     * ゲストには編集・削除ボタンが表示されないこと。
     */
    public function test_edit_and_delete_buttons_are_hidden_from_guest(): void
    {
        $book = Book::factory()->create();

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee(route('books.edit', $book))
            ->assertDontSee('name="_method" value="DELETE"', false);
    }

    /**
     * 存在しない ID を指定すると 404 になること（ルートモデルバインディングの検証）。
     */
    public function test_not_found_for_missing_book(): void
    {
        $this->get('/books/999')
            ->assertNotFound();
    }
}

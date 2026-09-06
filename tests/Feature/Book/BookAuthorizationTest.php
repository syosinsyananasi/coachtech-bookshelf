<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 書籍の認証・認可。
 * 認証: 登録・編集・削除はゲストをログイン画面へリダイレクトすること（auth ミドルウェア）。
 * 認可: 編集・削除は作成者以外を 403 で拒否すること（BookPolicy）。
 */
class BookAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * バリデーションを通過する入力値を返す。
     *
     * @return array<string, mixed>
     */
    private function validData(): array
    {
        return [
            'title' => 'リーダブルコード',
            'author' => 'Dustin Boswell',
            'isbn' => '9784873115658',
            'published_date' => '2012-06-23',
            'genres' => [Genre::factory()->create()->id],
        ];
    }

    // ---- 認証（ゲスト） ----

    /**
     * 未認証で書籍登録画面を開くとログイン画面にリダイレクトされること。
     */
    public function test_guest_cannot_view_create_screen(): void
    {
        $this->get(route('books.create'))
            ->assertRedirect(route('login'));
    }

    /**
     * 未認証の登録リクエストが弾かれ、書籍が保存されないこと。
     */
    public function test_guest_cannot_store_book(): void
    {
        $this->post(route('books.store'), $this->validData())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 未認証で書籍編集画面を開くとログイン画面にリダイレクトされること。
     */
    public function test_guest_cannot_view_edit_screen(): void
    {
        $book = Book::factory()->create();

        $this->get(route('books.edit', $book))
            ->assertRedirect(route('login'));
    }

    /**
     * 未認証の更新リクエストが弾かれ、書籍が変更されないこと。
     */
    public function test_guest_cannot_update_book(): void
    {
        $book = Book::factory()->create(['title' => '元のタイトル']);

        $this->put(route('books.update', $book), $this->validData())
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '元のタイトル']);
    }

    /**
     * 未認証の削除リクエストが弾かれ、書籍が残ること。
     */
    public function test_guest_cannot_delete_book(): void
    {
        $book = Book::factory()->create();

        $this->delete(route('books.destroy', $book))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    // ---- 認可（作成者以外） ----

    /**
     * 作成者以外が編集画面を開くと 403 になること。
     */
    public function test_other_user_cannot_view_edit_screen(): void
    {
        $book = Book::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('books.edit', $book))
            ->assertForbidden();
    }

    /**
     * 作成者以外の更新リクエストが 403 で拒否され、書籍が変更されないこと。
     */
    public function test_other_user_cannot_update_book(): void
    {
        $book = Book::factory()->create(['title' => '元のタイトル']);

        $this->actingAs(User::factory()->create())
            ->put(route('books.update', $book), $this->validData())
            ->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '元のタイトル']);
    }

    /**
     * 作成者以外の削除リクエストが 403 で拒否され、書籍が残ること。
     */
    public function test_other_user_cannot_delete_book(): void
    {
        $book = Book::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('books.destroy', $book))
            ->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }
}

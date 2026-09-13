<?php

namespace Tests\Feature\Favorite;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PG10 お気に入り一覧。ログインユーザーのお気に入りだけが 10件/ページ で表示されることを重点的に検証する。
 * 認証: ゲストはログイン画面へリダイレクトすること（auth ミドルウェア）。
 */
class FavoriteIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * 認証済みユーザーがお気に入り一覧を開けること。
     */
    public function test_index_is_displayed(): void
    {
        $this->actingAs($this->user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('お気に入り一覧');
    }

    /**
     * 未認証でお気に入り一覧を開くとログイン画面にリダイレクトされること。
     */
    public function test_guest_cannot_view_index(): void
    {
        $this->get(route('favorites.index'))
            ->assertRedirect(route('login'));
    }

    /**
     * お気に入り登録した書籍のタイトル・著者・詳細リンクが表示されること。
     */
    public function test_favorite_books_are_displayed(): void
    {
        $book = Book::factory()->create(['title' => 'リーダブルコード', 'author' => 'Dustin Boswell']);
        $this->user->favoriteBooks()->attach($book);

        $this->actingAs($this->user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('リーダブルコード')
            ->assertSee('Dustin Boswell')
            ->assertSee(route('books.show', $book));
    }

    /**
     * 他のユーザーのお気に入りは表示されないこと。
     */
    public function test_other_users_favorites_are_not_displayed(): void
    {
        $mine = Book::factory()->create(['title' => '自分のお気に入り']);
        $others = Book::factory()->create(['title' => '他人のお気に入り']);
        $this->user->favoriteBooks()->attach($mine);
        User::factory()->create()->favoriteBooks()->attach($others);

        $this->actingAs($this->user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('自分のお気に入り')
            ->assertDontSee('他人のお気に入り');
    }

    /**
     * お気に入りに登録していない書籍は表示されないこと。
     */
    public function test_unfavorited_books_are_not_displayed(): void
    {
        Book::factory()->create(['title' => '未登録の書籍']);

        $this->actingAs($this->user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertDontSee('未登録の書籍');
    }

    /**
     * 画面に描画された書籍数を数える。タイトルは画像の alt にも出るため、詳細リンクの数で判定する。
     */
    private function countBooks(string $content): int
    {
        return preg_match_all('#href="'.preg_quote(url('/books'), '#').'/\d+"#', $content);
    }

    /**
     * 11件登録すると 1ページ目に 10件 だけ表示されること。
     */
    public function test_favorites_are_paginated_by_ten(): void
    {
        $this->user->favoriteBooks()->attach(Book::factory()->count(11)->create());

        $content = $this->actingAs($this->user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(10, $this->countBooks($content));
    }

    /**
     * 2ページ目に残りの 1件 が表示されること。
     */
    public function test_second_page_contains_remaining_book(): void
    {
        $this->user->favoriteBooks()->attach(Book::factory()->count(11)->create());

        $content = $this->actingAs($this->user)
            ->get(route('favorites.index', ['page' => 2]))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, $this->countBooks($content));
    }

    /**
     * お気に入りが 0件 のとき空メッセージと書籍一覧へのリンクが表示されること。
     */
    public function test_empty_message_is_displayed_when_no_favorites(): void
    {
        $this->actingAs($this->user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('お気に入りに登録された書籍はありません。')
            ->assertSee(route('books.index'));
    }
}

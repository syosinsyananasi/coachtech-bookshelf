<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PG01 書籍一覧。公開ページとして 10件/ページ・最新順で表示され、各書籍にジャンルが付くこと。
 */
class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストでも一覧を開けること（公開ページ）。
     */
    public function test_index_is_accessible_by_guest(): void
    {
        $this->get(route('books.index'))
            ->assertOk();
    }

    /**
     * ルート URL（/）が書籍一覧にリダイレクトされること。
     */
    public function test_root_redirects_to_index(): void
    {
        $this->get('/')
            ->assertRedirect(route('books.index'));
    }

    /**
     * 一覧に書籍のタイトルと著者が表示されること。
     */
    public function test_titles_and_authors_are_displayed(): void
    {
        Book::factory()->create(['title' => 'リーダブルコード', 'author' => 'Dustin Boswell']);
        Book::factory()->create(['title' => '吾輩は猫である', 'author' => '夏目漱石']);

        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('リーダブルコード')
            ->assertSee('Dustin Boswell')
            ->assertSee('吾輩は猫である')
            ->assertSee('夏目漱石');
    }

    /**
     * 各書籍に紐づくジャンル名が表示されること（Eager Loading した genres が描画される）。
     */
    public function test_genres_are_displayed_for_each_book(): void
    {
        $book = Book::factory()->create();
        $book->genres()->attach([
            Genre::factory()->create(['name' => '技術書'])->id,
            Genre::factory()->create(['name' => 'ビジネス'])->id,
        ]);

        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('技術書')
            ->assertSee('ビジネス');
    }

    /**
     * 書籍が 10件/ページ で区切られること。
     */
    public function test_books_are_paginated_by_ten(): void
    {
        Book::factory()->count(11)->create();

        $this->get(route('books.index'))
            ->assertOk()
            ->assertViewHas('books', fn ($books) => $books->count() === 10 && $books->total() === 11);
    }

    /**
     * 2ページ目に残りの書籍が表示されること。
     */
    public function test_second_page_contains_remaining_book(): void
    {
        Book::factory()->count(11)->create();

        $this->get(route('books.index').'?page=2')
            ->assertOk()
            ->assertViewHas('books', fn ($books) => $books->count() === 1);
    }

    /**
     * 登録日時が新しい書籍が先頭に来ること（latest() の検証）。
     */
    public function test_books_are_ordered_by_latest(): void
    {
        Book::factory()->create(['title' => '古い本', 'created_at' => now()->subDay()]);
        Book::factory()->create(['title' => '新しい本', 'created_at' => now()]);

        $this->get(route('books.index'))
            ->assertOk()
            ->assertViewHas('books', fn ($books) => $books->first()->title === '新しい本');
    }

    /**
     * 書籍が1件も無いとき、空状態メッセージが表示されること。
     */
    public function test_empty_message_is_displayed_when_no_books_exist(): void
    {
        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('書籍が登録されていません。');
    }
}

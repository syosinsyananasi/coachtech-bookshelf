<?php

namespace Tests\Feature\Genre;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PG06 ジャンル詳細。紐づく書籍が 10件/ページでページネーションされること。
 */
class GenreShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 詳細画面にジャンル名が表示されること。
     */
    public function test_genre_name_is_displayed(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '小説']);

        $this->actingAs($user)
            ->get(route('genres.show', $genre))
            ->assertOk()
            ->assertSee('小説');
    }

    /**
     * 紐づく書籍が 10件/ページ で区切られること。
     */
    public function test_books_are_paginated_by_ten(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $genre->books()->attach(Book::factory()->count(11)->create());

        $this->actingAs($user)
            ->get(route('genres.show', $genre))
            ->assertOk()
            ->assertViewHas('books', fn ($books) => $books->count() === 10 && $books->total() === 11);
    }

    /**
     * 2ページ目に残りの書籍が表示されること。
     */
    public function test_second_page_contains_remaining_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $genre->books()->attach(Book::factory()->count(11)->create());

        $this->actingAs($user)
            ->get(route('genres.show', $genre).'?page=2')
            ->assertOk()
            ->assertViewHas('books', fn ($books) => $books->count() === 1);
    }

    /**
     * 書籍が紐づいていないとき、空状態メッセージが表示されること。
     */
    public function test_empty_message_is_displayed_when_no_books_are_linked(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $this->actingAs($user)
            ->get(route('genres.show', $genre))
            ->assertOk()
            ->assertSee('このジャンルの書籍はまだ登録されていません。');
    }
}

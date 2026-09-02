<?php

namespace Tests\Feature\Genre;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PG05 ジャンル一覧。ジャンル名と紐づく書籍数が表示されること。
 */
class GenreIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 一覧に登録済みのジャンル名がすべて表示されること。
     */
    public function test_genre_names_are_displayed(): void
    {
        $user = User::factory()->create();
        Genre::factory()->create(['name' => '小説']);
        Genre::factory()->create(['name' => '技術書']);

        $this->actingAs($user)
            ->get(route('genres.index'))
            ->assertOk()
            ->assertSee('小説')
            ->assertSee('技術書');
    }

    /**
     * 一覧に紐づく書籍数が表示されること（withCount が効いていることの検証）。
     */
    public function test_book_count_is_displayed(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '小説']);
        $genre->books()->attach(Book::factory()->count(2)->create());

        $this->actingAs($user)
            ->get(route('genres.index'))
            ->assertOk()
            ->assertSee('2冊');
    }

    /**
     * 書籍が紐づいていないジャンルは 0冊 と表示されること。
     */
    public function test_book_count_is_zero_when_no_books_are_linked(): void
    {
        $user = User::factory()->create();
        Genre::factory()->create(['name' => '小説']);

        $this->actingAs($user)
            ->get(route('genres.index'))
            ->assertOk()
            ->assertSee('0冊');
    }

    /**
     * ジャンルが1件も無いとき、空状態メッセージが表示されること。
     */
    public function test_empty_message_is_displayed_when_no_genres_exist(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('genres.index'))
            ->assertOk()
            ->assertSee('ジャンルが登録されていません。');
    }
}

<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AP01 書籍一覧API。JSON の構造、ジャンル・平均評価・件数の付与、検索・絞り込み・ページネーションを検証する。
 * 検索パラメータのバリデーションは IndexBookRequest のルールに対応させる。
 */
class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証なしで 200 が返り、data / links / meta の構造になっていること。
     */
    public function test_index_returns_paginated_json(): void
    {
        Book::factory()->count(3)->create();

        $this->getJson(route('api.v1.books.index'))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'title', 'author', 'isbn', 'published_date', 'genres', 'average_rating', 'reviews_count']],
                'links',
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    /**
     * 各書籍にジャンル・平均評価・レビュー件数が含まれること（with / withAvg / withCount の検証）。
     */
    public function test_each_book_includes_genres_rating_and_count(): void
    {
        $book = Book::factory()->create();
        $book->genres()->attach(Genre::factory()->create(['name' => '技術書']));
        Review::factory()->create(['book_id' => $book->id, 'rating' => 4]);
        Review::factory()->create(['book_id' => $book->id, 'rating' => 5]);

        $this->getJson(route('api.v1.books.index'))
            ->assertOk()
            ->assertJsonPath('data.0.genres.0.name', '技術書')
            ->assertJsonPath('data.0.average_rating', 4.5)
            ->assertJsonPath('data.0.reviews_count', 2);
    }

    /**
     * レビューが無い書籍は平均評価が null、件数が 0 になること。
     */
    public function test_book_without_reviews_has_null_rating(): void
    {
        Book::factory()->create();

        $this->getJson(route('api.v1.books.index'))
            ->assertOk()
            ->assertJsonPath('data.0.average_rating', null)
            ->assertJsonPath('data.0.reviews_count', 0);
    }

    /**
     * 一覧には reviews 配列を含めないこと（詳細のみ）。
     */
    public function test_index_does_not_include_reviews(): void
    {
        Book::factory()->create();

        $this->getJson(route('api.v1.books.index'))
            ->assertOk()
            ->assertJsonMissingPath('data.0.reviews');
    }

    /**
     * keyword がタイトルまたは著者名に部分一致する書籍だけを返すこと。
     */
    public function test_keyword_filters_by_title_or_author(): void
    {
        Book::factory()->create(['title' => '吾輩は猫である', 'author' => '夏目漱石']);
        Book::factory()->create(['title' => 'こころ', 'author' => '夏目漱石']);
        Book::factory()->create(['title' => 'リーダブルコード', 'author' => 'Dustin Boswell']);

        $this->getJson(route('api.v1.books.index', ['keyword' => '漱石']))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson(route('api.v1.books.index', ['keyword' => '猫']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', '吾輩は猫である');
    }

    /**
     * genre_id に紐づく書籍だけを返すこと。
     */
    public function test_genre_id_filters_books(): void
    {
        $genre = Genre::factory()->create();
        $matched = Book::factory()->create(['title' => '該当する書籍']);
        $matched->genres()->attach($genre);
        Book::factory()->create(['title' => '該当しない書籍']);

        $this->getJson(route('api.v1.books.index', ['genre_id' => $genre->id]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', '該当する書籍');
    }

    /**
     * 既定で 20件/ページ、per_page で件数を変えられること。
     */
    public function test_pagination_and_per_page(): void
    {
        Book::factory()->count(21)->create();

        $this->getJson(route('api.v1.books.index'))
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 21)
            ->assertJsonPath('meta.per_page', 20);

        $this->getJson(route('api.v1.books.index', ['per_page' => 5, 'page' => 5]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 5)
            ->assertJsonPath('meta.per_page', 5);
    }

    /**
     * 最新の登録順に並ぶこと。
     */
    public function test_books_are_ordered_by_latest(): void
    {
        Book::factory()->create(['title' => '古い書籍', 'created_at' => now()->subDay()]);
        Book::factory()->create(['title' => '新しい書籍', 'created_at' => now()]);

        $this->getJson(route('api.v1.books.index'))
            ->assertOk()
            ->assertJsonPath('data.0.title', '新しい書籍')
            ->assertJsonPath('data.1.title', '古い書籍');
    }

    /**
     * 存在しない genre_id を指定すると 422 と日本語メッセージが返ること。
     */
    public function test_genre_id_must_exist(): void
    {
        $this->getJson(route('api.v1.books.index', ['genre_id' => 999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['genre_id' => '指定されたジャンルは存在しません。']);
    }

    /**
     * per_page が 101 のとき 422 になること（上限 100）。
     */
    public function test_per_page_cannot_exceed100(): void
    {
        $this->getJson(route('api.v1.books.index', ['per_page' => 101]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page' => '1ページあたりの件数は100以下で入力してください。']);
    }
}

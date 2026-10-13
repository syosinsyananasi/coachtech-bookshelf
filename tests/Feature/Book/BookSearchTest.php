<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ★PG01 書籍一覧の検索・絞り込み・ソート（BookIndexRequest + BookController::index）。
 * パラメータ名と sort の値は Advanced ブランチの Blade に合わせ、
 * バリデーション失敗時は条件なしの一覧へ戻してフラッシュでエラーを表示する（PM 回答 Q-016）。
 */
class BookSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 一覧の表示順でタイトルの配列を返す。
     *
     * @return array<int, string>
     */
    private function titlesOf(string $query = ''): array
    {
        return $this->get(route('books.index').$query)
            ->assertOk()
            ->viewData('books')
            ->pluck('title')
            ->all();
    }

    // ---- 絞り込み ----

    /**
     * keyword がタイトルまたは著者名の部分一致で効くこと（whereAny の検証）。
     */
    public function test_keyword_filters_by_title_or_author(): void
    {
        Book::factory()->create(['title' => 'リーダブルコード', 'author' => 'Dustin Boswell']);
        Book::factory()->create(['title' => '吾輩は猫である', 'author' => '夏目漱石']);
        Book::factory()->create(['title' => '坊っちゃん', 'author' => '夏目漱石']);

        $this->assertSame(['リーダブルコード'], $this->titlesOf('?keyword=コード'));
        $this->assertCount(2, $this->titlesOf('?keyword=夏目'));
    }

    /**
     * genre で指定したジャンルに紐づく書籍だけになること（whereRelation の検証）。
     */
    public function test_genre_filters_books(): void
    {
        $novel = Genre::factory()->create(['name' => '小説']);
        $tech = Genre::factory()->create(['name' => '技術書']);
        Book::factory()->create(['title' => '吾輩は猫である'])->genres()->attach($novel);
        Book::factory()->create(['title' => 'リーダブルコード'])->genres()->attach($tech);

        $this->assertSame(['吾輩は猫である'], $this->titlesOf("?genre={$novel->id}"));
    }

    /**
     * keyword と genre を同時に指定すると両方の条件で絞られること。
     */
    public function test_keyword_and_genre_filters_combine(): void
    {
        $novel = Genre::factory()->create();
        Book::factory()->create(['title' => '吾輩は猫である', 'author' => '夏目漱石'])->genres()->attach($novel);
        Book::factory()->create(['title' => '坊っちゃん', 'author' => '夏目漱石']);

        $this->assertSame(['吾輩は猫である'], $this->titlesOf("?keyword=夏目&genre={$novel->id}"));
    }

    /**
     * 検索中は件数が表示されること。
     */
    public function test_result_count_is_displayed(): void
    {
        Book::factory()->create(['title' => 'リーダブルコード']);
        Book::factory()->create(['title' => '吾輩は猫である']);

        $this->get(route('books.index').'?keyword=コード')
            ->assertOk()
            ->assertSee('検索結果: 1件');
    }

    // ---- 並び順 ----

    /**
     * sort=latest は登録日時の新しい順であること（機能要件の正式な値。PM 回答 Q-029）。
     */
    public function test_sort_latest(): void
    {
        Book::factory()->create(['title' => '古い本', 'created_at' => now()->subDay()]);
        Book::factory()->create(['title' => '新しい本', 'created_at' => now()]);

        $this->assertSame(['新しい本', '古い本'], $this->titlesOf('?sort=latest'));
    }

    /**
     * Blade が送る sort=newest も latest と同じ新しい順であること。sort 未指定の既定の順は BookIndexTest で検証する。
     */
    public function test_sort_newest(): void
    {
        Book::factory()->create(['title' => '古い本', 'created_at' => now()->subDay()]);
        Book::factory()->create(['title' => '新しい本', 'created_at' => now()]);

        $this->assertSame(['新しい本', '古い本'], $this->titlesOf('?sort=newest'));
    }

    /**
     * sort=oldest は登録日時の古い順であること。
     */
    public function test_sort_oldest(): void
    {
        Book::factory()->create(['title' => '古い本', 'created_at' => now()->subDay()]);
        Book::factory()->create(['title' => '新しい本', 'created_at' => now()]);

        $this->assertSame(['古い本', '新しい本'], $this->titlesOf('?sort=oldest'));
    }

    /**
     * sort=title はタイトルの昇順であること。
     */
    public function test_sort_title(): void
    {
        Book::factory()->create(['title' => 'Clean Code']);
        Book::factory()->create(['title' => 'Agile']);
        Book::factory()->create(['title' => 'Book']);

        $this->assertSame(['Agile', 'Book', 'Clean Code'], $this->titlesOf('?sort=title'));
    }

    /**
     * sort=rating は平均評価の高い順で、レビューが無い書籍は末尾に来ること（機能要件）。
     */
    public function test_sort_rating_puts_unreviewed_books_last(): void
    {
        $user = User::factory()->create();
        $low = Book::factory()->create(['title' => '評価3']);
        $none = Book::factory()->create(['title' => 'レビューなし']);
        $high = Book::factory()->create(['title' => '評価5']);
        Review::factory()->create(['book_id' => $low->id, 'user_id' => $user->id, 'rating' => 3]);
        Review::factory()->create(['book_id' => $high->id, 'user_id' => $user->id, 'rating' => 5]);

        $this->assertSame(['評価5', '評価3', 'レビューなし'], $this->titlesOf('?sort=rating'));
    }

    /**
     * sort=rating で平均が同点なら登録日時の新しい順であること。
     */
    public function test_sort_rating_ties_are_ordered_by_newest(): void
    {
        $user = User::factory()->create();
        $older = Book::factory()->create(['title' => '古い本', 'created_at' => now()->subDay()]);
        $newer = Book::factory()->create(['title' => '新しい本', 'created_at' => now()]);
        Review::factory()->create(['book_id' => $older->id, 'user_id' => $user->id, 'rating' => 4]);
        Review::factory()->create(['book_id' => $newer->id, 'user_id' => $user->id, 'rating' => 4]);

        $this->assertSame(['新しい本', '古い本'], $this->titlesOf('?sort=rating'));
    }

    // ---- バリデーションエラー ----

    /**
     * 存在しないジャンルを指定すると条件なしの一覧へ戻り、エラーがフラッシュされること。
     */
    public function test_unknown_genre_redirects_to_index_with_error(): void
    {
        $this->get(route('books.index').'?genre=999')
            ->assertRedirect(route('books.index'))
            ->assertSessionHas('error', '指定されたジャンルは存在しません。');
    }

    /**
     * 不正な sort 値を指定すると条件なしの一覧へ戻り、エラーがフラッシュされること。
     */
    public function test_invalid_sort_redirects_to_index_with_error(): void
    {
        $this->get(route('books.index').'?sort=price')
            ->assertRedirect(route('books.index'))
            ->assertSessionHas('error', '並び順の指定が正しくありません。');
    }

    /**
     * keyword が 256文字 のとき max エラーで一覧へ戻ること。
     */
    public function test_too_long_keyword_redirects_to_index_with_error(): void
    {
        $this->get(route('books.index').'?keyword='.str_repeat('a', 256))
            ->assertRedirect(route('books.index'))
            ->assertSessionHas('error', 'キーワードは255文字以内で入力してください。');
    }

    /**
     * フラッシュしたエラーが一覧画面の session('error') の枠に描画されること。
     */
    public function test_error_message_is_visible_on_screen(): void
    {
        $this->followingRedirects()
            ->get(route('books.index').'?genre=999')
            ->assertOk()
            ->assertSee('指定されたジャンルは存在しません。');
    }

    // ---- ページネーションとビュー ----

    /**
     * 検索条件がページネーションのリンクに引き継がれること（appends の検証）。
     */
    public function test_search_conditions_are_kept_in_pagination_links(): void
    {
        Book::factory()->count(11)->create(['title' => 'Laravel 入門']);

        $this->get(route('books.index').'?keyword=Laravel&sort=oldest')
            ->assertOk()
            ->assertSee('keyword=Laravel', false)
            ->assertSee('sort=oldest', false)
            ->assertSee('page=2', false);
    }

    /**
     * 絞り込みのプルダウン用に全ジャンルがビューへ渡ること。
     */
    public function test_all_genres_are_passed_to_view(): void
    {
        Genre::factory()->count(3)->create();

        $this->get(route('books.index'))
            ->assertOk()
            ->assertViewHas('genres', fn ($genres) => $genres->count() === 3);
    }
}

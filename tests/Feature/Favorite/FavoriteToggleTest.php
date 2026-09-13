<?php

namespace Tests\Feature\Favorite;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PG02 お気に入りトグル。中間テーブル favorites の追加/解除と、直前の画面へ戻ることを重点的に検証する。
 * 書籍詳細のハート表示（favoriteBooks->contains() による出し分け）も扱う。
 * 認証: ゲストはログイン画面へリダイレクトすること（auth ミドルウェア）。
 */
class FavoriteToggleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->book = Book::factory()->create();
    }

    /**
     * 未登録の書籍をお気に入りにすると中間テーブルに行が追加されること。
     */
    public function test_favorite_is_added(): void
    {
        $this->actingAs($this->user)
            ->post(route('favorites.toggle', $this->book));

        $this->assertDatabaseHas('favorites', ['user_id' => $this->user->id, 'book_id' => $this->book->id]);
        $this->assertDatabaseCount('favorites', 1);
    }

    /**
     * 登録済みの書籍をもう一度押すと解除されること（toggle() の検証）。
     */
    public function test_favorite_is_removed_on_second_request(): void
    {
        $this->user->favoriteBooks()->attach($this->book);

        $this->actingAs($this->user)
            ->post(route('favorites.toggle', $this->book));

        $this->assertDatabaseMissing('favorites', ['user_id' => $this->user->id, 'book_id' => $this->book->id]);
    }

    /**
     * 他のユーザーのお気に入りには影響しないこと。
     */
    public function test_other_users_favorites_are_not_affected(): void
    {
        $other = User::factory()->create();
        $other->favoriteBooks()->attach($this->book);

        $this->actingAs($this->user)
            ->post(route('favorites.toggle', $this->book));

        $this->assertDatabaseHas('favorites', ['user_id' => $other->id, 'book_id' => $this->book->id]);
        $this->assertDatabaseCount('favorites', 2);
    }

    /**
     * 書籍詳細から押すと書籍詳細へ、お気に入り一覧から押すと一覧へ戻ること（back() の検証）。
     */
    public function test_redirects_back_to_previous_page(): void
    {
        $this->actingAs($this->user)
            ->from(route('books.show', $this->book))
            ->post(route('favorites.toggle', $this->book))
            ->assertRedirect(route('books.show', $this->book));

        $this->actingAs($this->user)
            ->from(route('favorites.index'))
            ->post(route('favorites.toggle', $this->book))
            ->assertRedirect(route('favorites.index'));
    }

    /**
     * 存在しない書籍をお気に入りにすると 404 になること（ルートモデルバインディングの検証）。
     */
    public function test_not_found_for_missing_book(): void
    {
        $this->actingAs($this->user)
            ->post(route('favorites.toggle', 999))
            ->assertNotFound();

        $this->assertDatabaseCount('favorites', 0);
    }

    /**
     * 未認証のトグルリクエストが弾かれ、中間テーブルに行が追加されないこと。
     */
    public function test_guest_cannot_toggle(): void
    {
        $this->post(route('favorites.toggle', $this->book))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('favorites', 0);
    }

    // ---- 表示 ----

    /**
     * 未登録のとき書籍詳細に「お気に入りに追加」のボタンが描画されること。
     */
    public function test_add_button_is_visible_before_favorite(): void
    {
        $this->actingAs($this->user)
            ->get(route('books.show', $this->book))
            ->assertOk()
            ->assertSee('title="お気に入りに追加"', false)
            ->assertDontSee('title="お気に入りから削除"', false);
    }

    /**
     * 登録済みのとき書籍詳細に「お気に入りから削除」のボタンが描画されること。
     */
    public function test_remove_button_is_visible_after_favorite(): void
    {
        $this->user->favoriteBooks()->attach($this->book);

        $this->actingAs($this->user)
            ->get(route('books.show', $this->book))
            ->assertOk()
            ->assertSee('title="お気に入りから削除"', false)
            ->assertDontSee('title="お気に入りに追加"', false);
    }

    /**
     * ゲストにはお気に入りボタンではなくログインリンクが描画されること。
     */
    public function test_guest_sees_login_link_instead_of_favorite_button(): void
    {
        $this->get(route('books.show', $this->book))
            ->assertOk()
            ->assertDontSee(route('favorites.toggle', $this->book))
            ->assertSee(route('login'));
    }
}

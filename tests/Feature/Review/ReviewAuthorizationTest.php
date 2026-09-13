<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * レビューの認証・認可。
 * 認証: 投稿・編集・削除はゲストをログイン画面へリダイレクトすること（auth ミドルウェア）。
 * 認可: 編集・削除は投稿者以外を 403 で拒否すること（ReviewPolicy）。
 * 表示: 書籍詳細の編集・削除ボタンは投稿者本人にのみ描画されること（@can）。
 */
class ReviewAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private Book $book;

    private Review $review;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create();
        $this->book = Book::factory()->create();
        $this->review = Review::factory()->create([
            'user_id' => $this->author->id,
            'book_id' => $this->book->id,
            'comment' => '元のコメント',
        ]);
    }

    /**
     * バリデーションを通過する入力値を返す。
     *
     * @return array<string, mixed>
     */
    private function validData(): array
    {
        return [
            'rating' => 5,
            'comment' => '更新後のコメント',
        ];
    }

    // ---- 認証（ゲスト） ----

    /**
     * 未認証の投稿リクエストが弾かれ、レビューが保存されないこと。
     */
    public function test_guest_cannot_store_review(): void
    {
        $this->post(route('reviews.store', $this->book), $this->validData())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('reviews', 1);
    }

    /**
     * 未認証でレビュー編集画面を開くとログイン画面にリダイレクトされること。
     */
    public function test_guest_cannot_view_edit_screen(): void
    {
        $this->get(route('reviews.edit', $this->review))
            ->assertRedirect(route('login'));
    }

    /**
     * 未認証の更新リクエストが弾かれ、レビューが変更されないこと。
     */
    public function test_guest_cannot_update_review(): void
    {
        $this->put(route('reviews.update', $this->review), $this->validData())
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('reviews', ['id' => $this->review->id, 'comment' => '元のコメント']);
    }

    /**
     * 未認証の削除リクエストが弾かれ、レビューが残ること。
     */
    public function test_guest_cannot_delete_review(): void
    {
        $this->delete(route('reviews.destroy', $this->review))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('reviews', ['id' => $this->review->id]);
    }

    // ---- 認可（投稿者以外） ----

    /**
     * 投稿者以外が編集画面を開くと 403 になること。
     */
    public function test_other_user_cannot_view_edit_screen(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reviews.edit', $this->review))
            ->assertForbidden();
    }

    /**
     * 投稿者以外の更新リクエストが 403 で拒否され、レビューが変更されないこと。
     */
    public function test_other_user_cannot_update_review(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('reviews.update', $this->review), $this->validData())
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $this->review->id, 'comment' => '元のコメント']);
    }

    /**
     * 投稿者以外の削除リクエストが 403 で拒否され、レビューが残ること。
     */
    public function test_other_user_cannot_delete_review(): void
    {
        $this->actingAs(User::factory()->create())
            ->delete(route('reviews.destroy', $this->review))
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $this->review->id]);
    }

    // ---- 表示（@can） ----

    /**
     * 投稿者本人には書籍詳細に編集リンクと削除フォームが表示されること（@can が ReviewPolicy を参照している検証）。
     */
    public function test_edit_and_delete_buttons_are_visible_to_author(): void
    {
        $this->actingAs($this->author)
            ->get(route('books.show', $this->book))
            ->assertOk()
            ->assertSee(route('reviews.edit', $this->review))
            ->assertSee('name="_method" value="DELETE"', false);
    }

    /**
     * 投稿者以外には書籍詳細に編集リンクと削除フォームが表示されないこと。
     */
    public function test_edit_and_delete_buttons_are_hidden_from_other_user(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('books.show', $this->book))
            ->assertOk()
            ->assertDontSee(route('reviews.edit', $this->review))
            ->assertDontSee('name="_method" value="DELETE"', false);
    }
}

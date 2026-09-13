<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PG02 レビューへのいいね。中間テーブル review_likes のトグル（追加/解除）と、
 * 書籍詳細での「いいね済み」表示・件数を重点的に検証する。
 * 認証: ゲストはログイン画面へリダイレクトすること（auth ミドルウェア）。
 * 認可: 投稿者本人のいいねは 403 で拒否すること（ReviewPolicy）。
 */
class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Book $book;

    private Review $review;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->book = Book::factory()->create();
        $this->review = Review::factory()->create(['book_id' => $this->book->id]);
    }

    /**
     * 未登録のレビューにいいねすると中間テーブルに行が追加され、書籍詳細へリダイレクトされること。
     */
    public function test_like_is_added(): void
    {
        $this->actingAs($this->user)
            ->post(route('reviews.like', $this->review))
            ->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('review_likes', ['review_id' => $this->review->id, 'user_id' => $this->user->id]);
        $this->assertDatabaseCount('review_likes', 1);
    }

    /**
     * 登録済みのレビューにもう一度いいねすると解除されること（toggle() の検証）。
     */
    public function test_like_is_removed_on_second_request(): void
    {
        $this->review->likedByUsers()->attach($this->user);

        $this->actingAs($this->user)
            ->post(route('reviews.like', $this->review))
            ->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseMissing('review_likes', ['review_id' => $this->review->id, 'user_id' => $this->user->id]);
    }

    /**
     * 他のユーザーのいいねには影響せず、自分の分だけが追加・解除されること。
     */
    public function test_other_users_likes_are_not_affected(): void
    {
        $other = User::factory()->create();
        $this->review->likedByUsers()->attach($other);

        $this->actingAs($this->user)
            ->post(route('reviews.like', $this->review));

        $this->assertDatabaseHas('review_likes', ['review_id' => $this->review->id, 'user_id' => $other->id]);
        $this->assertDatabaseCount('review_likes', 2);
    }

    /**
     * 投稿者本人のいいねは 403 で拒否され、中間テーブルに行が追加されないこと（ReviewPolicy::like の検証）。
     */
    public function test_author_cannot_like_own_review(): void
    {
        $this->actingAs($this->review->user)
            ->post(route('reviews.like', $this->review))
            ->assertForbidden();

        $this->assertDatabaseCount('review_likes', 0);
    }

    /**
     * 存在しないレビューにいいねすると 404 になること（ルートモデルバインディングの検証）。
     */
    public function test_not_found_for_missing_review(): void
    {
        $this->actingAs($this->user)
            ->post(route('reviews.like', 999))
            ->assertNotFound();

        $this->assertDatabaseCount('review_likes', 0);
    }

    /**
     * 未認証のいいねリクエストが弾かれ、中間テーブルに行が追加されないこと。
     */
    public function test_guest_cannot_like(): void
    {
        $this->post(route('reviews.like', $this->review))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('review_likes', 0);
    }

    // ---- 表示 ----

    /**
     * いいね前は「いいね (0)」が描画され、「いいね済み」は表示されないこと。
     */
    public function test_like_button_is_visible_before_like(): void
    {
        $this->actingAs($this->user)
            ->get(route('books.show', $this->book))
            ->assertOk()
            ->assertSee('いいね (0)')
            ->assertDontSee('いいね済み');
    }

    /**
     * いいね後の書籍詳細に「いいね済み (1)」が描画されること（likedReviews->contains() の検証）。
     *
     * 同じ User インスタンスで事前に詳細画面を開くと likedReviews の結果がキャッシュされて
     * 判定が古いままになるため、このテストでは事前の GET を行わない。
     */
    public function test_liked_state_is_visible_after_like(): void
    {
        $this->actingAs($this->user)
            ->followingRedirects()
            ->post(route('reviews.like', $this->review))
            ->assertOk()
            ->assertSee('いいね済み (1)');
    }

    /**
     * 件数には他のユーザーのいいねも含まれること。
     */
    public function test_like_count_includes_other_users(): void
    {
        $this->review->likedByUsers()->attach(User::factory()->count(2)->create());

        $this->actingAs($this->user)
            ->get(route('books.show', $this->book))
            ->assertOk()
            ->assertSee('いいね (2)');
    }

    /**
     * ゲストには件数付きのログインリンクが表示され、いいねフォームは描画されないこと。
     */
    public function test_guest_sees_login_link_instead_of_like_form(): void
    {
        $this->review->likedByUsers()->attach($this->user);

        $this->get(route('books.show', $this->book))
            ->assertOk()
            ->assertSee('いいね (1)')
            ->assertDontSee(route('reviews.like', $this->review));
    }
}

<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PG02 レビュー投稿。バリデーションは StoreReviewRequest のルールに 1対1 で対応させる。
 * 投稿者がログインユーザー、対象書籍が URL の書籍になることを重点的に検証する。
 * 認証（ゲストの拒否）は ReviewAuthorizationTest で扱う。
 */
class ReviewStoreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => '鈴木花子']);
        $this->book = Book::factory()->create();
    }

    /**
     * バリデーションを通過する入力値を返す。
     *
     * @param  array<string, mixed>  $overrides  上書きしたい項目
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'rating' => 4,
            'comment' => '命名の章だけでも読む価値があります。',
        ], $overrides);
    }

    /**
     * 正常な入力でレビューが保存され、投稿者がログインユーザー・対象が URL の書籍になること。
     */
    public function test_review_is_stored_with_login_user_as_author(): void
    {
        $this->actingAs($this->user)
            ->post(route('reviews.store', $this->book), $this->validData());

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'rating' => 4,
            'comment' => '命名の章だけでも読む価値があります。',
        ]);
    }

    /**
     * 投稿後に書籍詳細へリダイレクトされ、成功メッセージがセッションに入ること。
     */
    public function test_redirects_to_show_with_success_message(): void
    {
        $this->actingAs($this->user)
            ->post(route('reviews.store', $this->book), $this->validData())
            ->assertRedirect(route('books.show', $this->book))
            ->assertSessionHas('success', 'レビューを投稿しました。');
    }

    /**
     * 投稿後の書籍詳細に成功メッセージと投稿したレビュー（投稿者名・コメント）が描画されること。
     */
    public function test_review_is_visible_on_screen(): void
    {
        $this->actingAs($this->user)
            ->followingRedirects()
            ->post(route('reviews.store', $this->book), $this->validData())
            ->assertOk()
            ->assertSee('レビューを投稿しました。')
            ->assertSee('鈴木花子')
            ->assertSee('命名の章だけでも読む価値があります。');
    }

    /**
     * 存在しない書籍へ投稿すると 404 になること（ルートモデルバインディングの検証）。
     */
    public function test_not_found_for_missing_book(): void
    {
        $this->actingAs($this->user)
            ->post(route('reviews.store', 999), $this->validData())
            ->assertNotFound();

        $this->assertDatabaseCount('reviews', 0);
    }

    /**
     * 評価が未選択のとき required エラーになり、保存されないこと。
     */
    public function test_rating_is_required(): void
    {
        $this->actingAs($this->user)
            ->post(route('reviews.store', $this->book), $this->validData(['rating' => '']))
            ->assertSessionHasErrors(['rating' => '評価は必須です。']);

        $this->assertDatabaseCount('reviews', 0);
    }

    /**
     * 評価に整数以外を送ると integer エラーになること。
     */
    public function test_rating_must_be_integer(): void
    {
        $this->actingAs($this->user)
            ->post(route('reviews.store', $this->book), $this->validData(['rating' => 'abc']))
            ->assertSessionHasErrors(['rating' => '評価は整数で入力してください。']);

        $this->assertDatabaseCount('reviews', 0);
    }

    /**
     * 評価が 0・6 のとき min / max エラーになること（1〜5 のみ許可）。
     */
    public function test_rating_must_be_between1_and5(): void
    {
        foreach ([0, 6] as $rating) {
            $this->actingAs($this->user)
                ->post(route('reviews.store', $this->book), $this->validData(['rating' => $rating]))
                ->assertSessionHasErrors(['rating' => '評価は1〜5の整数で入力してください。']);
        }

        $this->assertDatabaseCount('reviews', 0);
    }

    /**
     * 評価が境界値の 1 と 5 のとき保存できること。
     */
    public function test_rating_boundary_values_are_accepted(): void
    {
        foreach ([1, 5] as $rating) {
            $this->actingAs($this->user)
                ->post(route('reviews.store', $this->book), $this->validData(['rating' => $rating]))
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('reviews', 2);
    }

    /**
     * コメントが未入力のとき required エラーになること。
     */
    public function test_comment_is_required(): void
    {
        $this->actingAs($this->user)
            ->post(route('reviews.store', $this->book), $this->validData(['comment' => '']))
            ->assertSessionHasErrors(['comment' => 'コメントは必須です。']);

        $this->assertDatabaseCount('reviews', 0);
    }

    /**
     * コメントが1001文字のとき max エラーになること。
     */
    public function test_comment_cannot_exceed1000_characters(): void
    {
        $this->actingAs($this->user)
            ->post(route('reviews.store', $this->book), $this->validData(['comment' => str_repeat('あ', 1001)]))
            ->assertSessionHasErrors(['comment' => 'コメントは1000文字以内で入力してください。']);

        $this->assertDatabaseCount('reviews', 0);
    }

    /**
     * バリデーション失敗時、書籍詳細の投稿フォームにエラーメッセージが実際に描画されること。
     */
    public function test_validation_error_is_visible_on_screen(): void
    {
        $this->actingAs($this->user)
            ->from(route('books.show', $this->book))
            ->followingRedirects()
            ->post(route('reviews.store', $this->book), $this->validData(['comment' => '']))
            ->assertOk()
            ->assertSee('コメントは必須です。');
    }
}

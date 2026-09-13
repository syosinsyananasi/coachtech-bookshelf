<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PG09 レビュー編集。既存値の初期表示と更新内容の反映を重点的に検証する。
 * バリデーションは UpdateReviewRequest のルールに 1対1 で対応させる。
 * 認可（投稿者以外の拒否）は ReviewAuthorizationTest で扱う。
 */
class ReviewUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private Book $book;

    private Review $review;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create();
        $this->book = Book::factory()->create(['title' => 'リーダブルコード']);
        $this->review = Review::factory()->create([
            'user_id' => $this->author->id,
            'book_id' => $this->book->id,
            'rating' => 4,
            'comment' => '元のコメント',
        ]);
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
            'rating' => 5,
            'comment' => '更新後のコメント',
        ], $overrides);
    }

    /**
     * 編集画面に対象書籍名と既存のコメントが表示され、既存の評価にチェックが入っていること。
     */
    public function test_edit_screen_shows_current_values(): void
    {
        $content = $this->actingAs($this->author)
            ->get(route('reviews.edit', $this->review))
            ->assertOk()
            ->assertSee('レビューの編集')
            ->assertSee('リーダブルコード')
            ->assertSee('元のコメント')
            ->getContent();

        $this->assertMatchesRegularExpression('/value="4"[^>]*checked/', $content);
        $this->assertDoesNotMatchRegularExpression('/value="5"[^>]*checked/', $content);
    }

    /**
     * レビューが更新され、書籍詳細へリダイレクトされて成功メッセージがセッションに入ること。
     */
    public function test_review_is_updated(): void
    {
        $this->actingAs($this->author)
            ->put(route('reviews.update', $this->review), $this->validData())
            ->assertRedirect(route('books.show', $this->book))
            ->assertSessionHas('success', 'レビューを更新しました。');

        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
            'rating' => 5,
            'comment' => '更新後のコメント',
        ]);
    }

    /**
     * 更新後の書籍詳細に成功メッセージと更新後のコメントが描画されること。
     */
    public function test_success_message_is_visible_on_screen(): void
    {
        $this->actingAs($this->author)
            ->followingRedirects()
            ->put(route('reviews.update', $this->review), $this->validData())
            ->assertOk()
            ->assertSee('レビューを更新しました。')
            ->assertSee('更新後のコメント');
    }

    /**
     * 評価が未選択のとき required エラーになり、更新されないこと。
     */
    public function test_rating_is_required(): void
    {
        $this->actingAs($this->author)
            ->put(route('reviews.update', $this->review), $this->validData(['rating' => '']))
            ->assertSessionHasErrors(['rating' => '評価は必須です。']);

        $this->assertDatabaseHas('reviews', ['id' => $this->review->id, 'rating' => 4]);
    }

    /**
     * 評価が 0・6 のとき min / max エラーになること（1〜5 のみ許可）。
     */
    public function test_rating_must_be_between1_and5(): void
    {
        foreach ([0, 6] as $rating) {
            $this->actingAs($this->author)
                ->put(route('reviews.update', $this->review), $this->validData(['rating' => $rating]))
                ->assertSessionHasErrors(['rating' => '評価は1〜5の整数で入力してください。']);
        }

        $this->assertDatabaseHas('reviews', ['id' => $this->review->id, 'rating' => 4]);
    }

    /**
     * コメントが未入力のとき required エラーになり、更新されないこと。
     */
    public function test_comment_is_required(): void
    {
        $this->actingAs($this->author)
            ->put(route('reviews.update', $this->review), $this->validData(['comment' => '']))
            ->assertSessionHasErrors(['comment' => 'コメントは必須です。']);

        $this->assertDatabaseHas('reviews', ['id' => $this->review->id, 'comment' => '元のコメント']);
    }

    /**
     * コメントが1001文字のとき max エラーになること。
     */
    public function test_comment_cannot_exceed1000_characters(): void
    {
        $this->actingAs($this->author)
            ->put(route('reviews.update', $this->review), $this->validData(['comment' => str_repeat('あ', 1001)]))
            ->assertSessionHasErrors(['comment' => 'コメントは1000文字以内で入力してください。']);

        $this->assertDatabaseHas('reviews', ['id' => $this->review->id, 'comment' => '元のコメント']);
    }

    /**
     * バリデーション失敗時、編集フォームにエラーメッセージが実際に描画されること。
     */
    public function test_validation_error_is_visible_on_screen(): void
    {
        $this->actingAs($this->author)
            ->from(route('reviews.edit', $this->review))
            ->followingRedirects()
            ->put(route('reviews.update', $this->review), $this->validData(['comment' => '']))
            ->assertOk()
            ->assertSee('コメントは必須です。');
    }
}

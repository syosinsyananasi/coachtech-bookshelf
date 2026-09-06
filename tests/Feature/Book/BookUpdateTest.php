<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PG04 書籍編集。既存値の初期表示、ISBN 一意チェックからの自身除外、ジャンルの sync を重点的に検証する。
 * 認可（作成者以外の拒否）は BookAuthorizationTest で扱う。
 */
class BookUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->book = Book::factory()->create([
            'user_id' => $this->owner->id,
            'title' => 'リーダブルコード',
            'author' => 'Dustin Boswell',
            'isbn' => '9784873115658',
            'published_date' => '2012-06-23',
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
            'title' => 'リーダブルコード 第2版',
            'author' => 'Dustin Boswell',
            'isbn' => '9784873115658',
            'published_date' => '2020-01-01',
            'description' => '改訂版。',
            'image_url' => 'https://placehold.co/200x300',
            'genres' => [Genre::factory()->create()->id],
        ], $overrides);
    }

    /**
     * 編集画面に既存の値が初期値として表示されること。
     */
    public function test_edit_screen_shows_current_values(): void
    {
        $this->actingAs($this->owner)
            ->get(route('books.edit', $this->book))
            ->assertOk()
            ->assertSee('書籍の編集')
            ->assertSee('value="リーダブルコード"', false)
            ->assertSee('value="Dustin Boswell"', false)
            ->assertSee('value="9784873115658"', false)
            ->assertSee('value="2012-06-23"', false);
    }

    /**
     * 編集画面で書籍に紐づくジャンルにチェックが入っていること。
     */
    public function test_edit_screen_has_current_genres_checked(): void
    {
        $checked = Genre::factory()->create();
        $unchecked = Genre::factory()->create();
        $this->book->genres()->attach($checked);

        $content = $this->actingAs($this->owner)
            ->get(route('books.edit', $this->book))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/value="'.$checked->id.'"[^>]*checked/', $content);
        $this->assertDoesNotMatchRegularExpression('/value="'.$unchecked->id.'"[^>]*checked/', $content);
    }

    /**
     * 書籍が更新され、詳細画面へリダイレクトされて成功メッセージがセッションに入ること。
     */
    public function test_book_is_updated(): void
    {
        $this->actingAs($this->owner)
            ->put(route('books.update', $this->book), $this->validData())
            ->assertRedirect(route('books.show', $this->book))
            ->assertSessionHas('success', '書籍を更新しました。');

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => 'リーダブルコード 第2版',
            'published_date' => '2020-01-01',
            'description' => '改訂版。',
        ]);
    }

    /**
     * ジャンルが送信内容ぴったりに置き換わること（外したジャンルは detach、新しいジャンルは attach）。
     */
    public function test_genres_are_synced(): void
    {
        $old = Genre::factory()->create();
        $new = Genre::factory()->create();
        $this->book->genres()->attach($old);

        $this->actingAs($this->owner)
            ->put(route('books.update', $this->book), $this->validData(['genres' => [$new->id]]));

        $this->assertDatabaseMissing('book_genre', ['book_id' => $this->book->id, 'genre_id' => $old->id]);
        $this->assertDatabaseHas('book_genre', ['book_id' => $this->book->id, 'genre_id' => $new->id]);
    }

    /**
     * ISBN を変えずに更新できること。
     * Rule::unique()->ignore() が効いていることの検証で、ignore を外すと自分自身にヒットして必ず失敗する。
     */
    public function test_same_isbn_can_be_submitted(): void
    {
        $this->actingAs($this->owner)
            ->put(route('books.update', $this->book), $this->validData(['isbn' => '9784873115658']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('books.show', $this->book));
    }

    /**
     * 他の書籍と同じ ISBN にすると unique エラーになること。
     */
    public function test_isbn_must_be_unique_against_other_books(): void
    {
        Book::factory()->create(['isbn' => '9784101010014']);

        $this->actingAs($this->owner)
            ->put(route('books.update', $this->book), $this->validData(['isbn' => '9784101010014']))
            ->assertSessionHasErrors(['isbn' => 'そのISBNは既に使用されています。']);

        $this->assertDatabaseHas('books', ['id' => $this->book->id, 'isbn' => '9784873115658']);
    }

    /**
     * タイトルが未入力のとき required エラーになり、更新されないこと。
     */
    public function test_title_is_required(): void
    {
        $this->actingAs($this->owner)
            ->put(route('books.update', $this->book), $this->validData(['title' => '']))
            ->assertSessionHasErrors(['title' => 'タイトルは必須です。']);

        $this->assertDatabaseHas('books', ['id' => $this->book->id, 'title' => 'リーダブルコード']);
    }

    /**
     * ジャンルを全て外すと required エラーになり、既存の紐付けが残ること。
     */
    public function test_genres_is_required(): void
    {
        $genre = Genre::factory()->create();
        $this->book->genres()->attach($genre);

        $this->actingAs($this->owner)
            ->put(route('books.update', $this->book), $this->validData(['genres' => []]))
            ->assertSessionHasErrors(['genres' => 'ジャンルは1つ以上選択してください。']);

        $this->assertDatabaseHas('book_genre', ['book_id' => $this->book->id, 'genre_id' => $genre->id]);
    }

    /**
     * バリデーション失敗時、編集フォームにエラーメッセージが実際に描画されること。
     */
    public function test_validation_error_is_visible_on_screen(): void
    {
        $this->actingAs($this->owner)
            ->from(route('books.edit', $this->book))
            ->followingRedirects()
            ->put(route('books.update', $this->book), $this->validData(['title' => '']))
            ->assertOk()
            ->assertSee('タイトルは必須です。');
    }
}

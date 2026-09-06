<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PG03 書籍登録。バリデーションは StoreBookRequest のルールに 1対1 で対応させる。
 * 登録者がログインユーザーになること、ジャンルが中間テーブルへ紐付くことを重点的に検証する。
 */
class BookStoreTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    /**
     * バリデーションを通過する入力値を返す。ジャンルは1件作成して紐付ける。
     *
     * @param  array<string, mixed>  $overrides  上書きしたい項目
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'リーダブルコード',
            'author' => 'Dustin Boswell',
            'isbn' => '9784873115658',
            'published_date' => '2012-06-23',
            'description' => '読みやすいコードを書くための技術。',
            'image_url' => 'https://placehold.co/200x300',
            'genres' => [Genre::factory()->create()->id],
        ], $overrides);
    }

    /**
     * 認証済みユーザーが書籍登録画面を開けること。
     */
    public function test_create_screen_is_displayed(): void
    {
        Genre::factory()->create();

        $this->actingAs($this->user())
            ->get(route('books.create'))
            ->assertOk()
            ->assertSee('書籍の登録');
    }

    /**
     * 登録画面に全ジャンルがチェックボックスとして表示されること。
     */
    public function test_create_screen_lists_all_genres(): void
    {
        Genre::factory()->create(['name' => '小説']);
        Genre::factory()->create(['name' => '技術書']);

        $this->actingAs($this->user())
            ->get(route('books.create'))
            ->assertOk()
            ->assertSee('小説')
            ->assertSee('技術書');
    }

    /**
     * 正常な入力で書籍が保存され、登録者がログインユーザーになること。
     */
    public function test_book_is_stored_with_login_user_as_owner(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('books.store'), $this->validData());

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'リーダブルコード',
            'author' => 'Dustin Boswell',
            'isbn' => '9784873115658',
            'published_date' => '2012-06-23',
            'description' => '読みやすいコードを書くための技術。',
            'image_url' => 'https://placehold.co/200x300',
        ]);
    }

    /**
     * 選択したジャンルが中間テーブルに紐付くこと（genres()->sync() の検証）。
     */
    public function test_genres_are_attached(): void
    {
        $genres = Genre::factory()->count(2)->create();

        $this->actingAs($this->user())
            ->post(route('books.store'), $this->validData(['genres' => $genres->pluck('id')->all()]));

        $book = Book::first();
        foreach ($genres as $genre) {
            $this->assertDatabaseHas('book_genre', ['book_id' => $book->id, 'genre_id' => $genre->id]);
        }
        $this->assertDatabaseCount('book_genre', 2);
    }

    /**
     * 登録後に詳細画面へリダイレクトされ、成功メッセージがセッションに入ること。
     */
    public function test_redirects_to_show_with_success_message(): void
    {
        $this->actingAs($this->user())
            ->post(route('books.store'), $this->validData())
            ->assertRedirect(route('books.show', Book::first()))
            ->assertSessionHas('success', '書籍を登録しました。');
    }

    /**
     * 説明と画像URL は任意項目のため、未入力でも保存できること。
     */
    public function test_optional_fields_can_be_omitted(): void
    {
        $this->actingAs($this->user())
            ->post(route('books.store'), $this->validData(['description' => '', 'image_url' => '']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('books', ['isbn' => '9784873115658', 'description' => null, 'image_url' => null]);
    }

    /**
     * タイトルが未入力のとき required エラーになり、保存されないこと。
     */
    public function test_title_is_required(): void
    {
        $this->actingAs($this->user())
            ->post(route('books.store'), $this->validData(['title' => '']))
            ->assertSessionHasErrors(['title' => 'タイトルは必須です。']);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * タイトルが256文字のとき max エラーになること。
     */
    public function test_title_cannot_exceed255_characters(): void
    {
        $this->actingAs($this->user())
            ->post(route('books.store'), $this->validData(['title' => str_repeat('あ', 256)]))
            ->assertSessionHasErrors(['title' => 'タイトルは255文字以内で入力してください。']);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 著者名が未入力のとき required エラーになること。
     */
    public function test_author_is_required(): void
    {
        $this->actingAs($this->user())
            ->post(route('books.store'), $this->validData(['author' => '']))
            ->assertSessionHasErrors(['author' => '著者名は必須です。']);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 著者名が256文字のとき max エラーになること。
     */
    public function test_author_cannot_exceed255_characters(): void
    {
        $this->actingAs($this->user())
            ->post(route('books.store'), $this->validData(['author' => str_repeat('あ', 256)]))
            ->assertSessionHasErrors(['author' => '著者名は255文字以内で入力してください。']);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * ISBN が未入力のとき required エラーになること。
     */
    public function test_isbn_is_required(): void
    {
        $this->actingAs($this->user())
            ->post(route('books.store'), $this->validData(['isbn' => '']))
            ->assertSessionHasErrors(['isbn' => 'ISBNは必須です。']);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * ISBN が12桁・14桁のとき size エラーになること（13桁ちょうどのみ許可）。
     */
    public function test_isbn_must_be13_characters(): void
    {
        foreach (['978487311565', '97848731156581'] as $isbn) {
            $this->actingAs($this->user())
                ->post(route('books.store'), $this->validData(['isbn' => $isbn]))
                ->assertSessionHasErrors(['isbn' => 'ISBNは13桁で入力してください。']);
        }

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 既存の書籍と同じ ISBN で unique エラーになること。
     */
    public function test_isbn_must_be_unique(): void
    {
        Book::factory()->create(['isbn' => '9784873115658']);

        $this->actingAs($this->user())
            ->post(route('books.store'), $this->validData(['isbn' => '9784873115658']))
            ->assertSessionHasErrors(['isbn' => 'そのISBNは既に使用されています。']);

        $this->assertDatabaseCount('books', 1);
    }

    /**
     * 出版日が未入力のとき required エラーになること。
     */
    public function test_published_date_is_required(): void
    {
        $this->actingAs($this->user())
            ->post(route('books.store'), $this->validData(['published_date' => '']))
            ->assertSessionHasErrors(['published_date' => '出版日は必須です。']);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 出版日が日付として不正なとき date エラーになること。
     */
    public function test_published_date_must_be_valid_date(): void
    {
        $this->actingAs($this->user())
            ->post(route('books.store'), $this->validData(['published_date' => '2012-13-45']))
            ->assertSessionHasErrors(['published_date' => '出版日は有効な日付形式で入力してください。']);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 画像URL が URL 形式でないとき url エラーになること。
     */
    public function test_image_url_must_be_url(): void
    {
        $this->actingAs($this->user())
            ->post(route('books.store'), $this->validData(['image_url' => 'not-a-url']))
            ->assertSessionHasErrors(['image_url' => '画像URLは有効なURL形式で入力してください。']);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 画像URL が256文字以上のとき max エラーになること。
     */
    public function test_image_url_cannot_exceed255_characters(): void
    {
        $this->actingAs($this->user())
            ->post(route('books.store'), $this->validData(['image_url' => 'https://example.com/'.str_repeat('a', 240)]))
            ->assertSessionHasErrors(['image_url' => '画像URLは255文字以内で入力してください。']);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * ジャンルが未選択（キー無し・空配列）のとき required エラーになること。
     */
    public function test_genres_is_required(): void
    {
        $data = $this->validData();
        unset($data['genres']);

        $this->actingAs($this->user())
            ->post(route('books.store'), $data)
            ->assertSessionHasErrors(['genres' => 'ジャンルは1つ以上選択してください。']);

        $this->actingAs($this->user())
            ->post(route('books.store'), $this->validData(['genres' => []]))
            ->assertSessionHasErrors(['genres' => 'ジャンルは1つ以上選択してください。']);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * ジャンルに配列以外を送ると array エラーになること。
     */
    public function test_genres_must_be_array(): void
    {
        $this->actingAs($this->user())
            ->post(route('books.store'), $this->validData(['genres' => '1']))
            ->assertSessionHasErrors(['genres' => 'ジャンルは配列で入力してください。']);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 存在しないジャンル ID を送ると exists エラーになること。
     */
    public function test_genre_must_exist(): void
    {
        $this->actingAs($this->user())
            ->post(route('books.store'), $this->validData(['genres' => [999]]))
            ->assertSessionHasErrors(['genres.0' => '選択されたジャンルは存在しません。']);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * バリデーション失敗時、登録フォームにエラーメッセージが実際に描画されること。
     *
     * セッションの検証だけでは Blade の @error ブロックが消えても気づけないため、
     * リダイレクト先の画面まで追って確認する（機能要件「失敗時→バリデーションエラーが表示される」）。
     */
    public function test_validation_error_is_visible_on_screen(): void
    {
        $this->actingAs($this->user())
            ->from(route('books.create'))
            ->followingRedirects()
            ->post(route('books.store'), $this->validData(['title' => '']))
            ->assertOk()
            ->assertSee('タイトルは必須です。');
    }
}

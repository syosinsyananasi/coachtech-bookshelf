<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * AP03 書籍登録API。バリデーションは Api\V1\StoreBookRequest のルールに 1対1 で対応させる。
 * 登録者がトークンの持ち主になること、ジャンルが中間テーブルへ紐付くこと、201 が返ることを重点的に検証する。
 * 認証（未認証の 401）は BookAuthorizationTest で扱う。
 */
class BookStoreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
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
     * 正常な入力で 201 が返り、登録した書籍の JSON が返ること。
     */
    public function test_book_is_created_with201(): void
    {
        $this->postJson(route('api.v1.books.store'), $this->validData())
            ->assertCreated()
            ->assertJsonPath('data.title', 'リーダブルコード')
            ->assertJsonPath('data.user_id', $this->user->id)
            ->assertJsonPath('data.reviews_count', 0)
            ->assertJsonStructure(['data' => ['id', 'genres']]);
    }

    /**
     * 書籍が保存され、登録者がトークンの持ち主になること（$request->user() 経由の作成の検証）。
     */
    public function test_book_is_stored_with_token_owner_as_owner(): void
    {
        $this->postJson(route('api.v1.books.store'), $this->validData());

        $this->assertDatabaseHas('books', [
            'user_id' => $this->user->id,
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

        $this->postJson(route('api.v1.books.store'), $this->validData(['genres' => $genres->pluck('id')->all()]))
            ->assertCreated()
            ->assertJsonCount(2, 'data.genres');

        $book = Book::first();
        $genres->each(fn (Genre $genre) => $this->assertDatabaseHas('book_genre', ['book_id' => $book->id, 'genre_id' => $genre->id]));
        $this->assertDatabaseCount('book_genre', 2);
    }

    /**
     * 説明と画像URL は任意項目のため、未指定でも保存できること。
     */
    public function test_optional_fields_can_be_omitted(): void
    {
        $data = $this->validData();
        unset($data['description'], $data['image_url']);

        $this->postJson(route('api.v1.books.store'), $data)
            ->assertCreated();

        $this->assertDatabaseHas('books', ['isbn' => '9784873115658', 'description' => null, 'image_url' => null]);
    }

    /**
     * 本文に user_id を送っても無視され、登録者はトークンの持ち主のままであること（なりすまし防止）。
     */
    public function test_user_id_in_request_body_is_ignored(): void
    {
        $other = User::factory()->create();

        $this->postJson(route('api.v1.books.store'), $this->validData(['user_id' => $other->id]))
            ->assertCreated()
            ->assertJsonPath('data.user_id', $this->user->id);

        $this->assertDatabaseHas('books', ['isbn' => '9784873115658', 'user_id' => $this->user->id]);
    }

    /**
     * タイトルが未入力のとき required エラーになること。
     */
    public function test_title_is_required(): void
    {
        $this->postJson(route('api.v1.books.store'), $this->validData(['title' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title' => 'タイトルは必須です。']);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * ISBN が 12桁 のとき size エラーになること。
     */
    public function test_isbn_must_be13_characters(): void
    {
        $this->postJson(route('api.v1.books.store'), $this->validData(['isbn' => '978487311565']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['isbn' => 'ISBNは13桁で入力してください。']);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 既存の書籍と同じ ISBN で unique エラーになること。
     */
    public function test_isbn_must_be_unique(): void
    {
        Book::factory()->create(['isbn' => '9784873115658']);

        $this->postJson(route('api.v1.books.store'), $this->validData(['isbn' => '9784873115658']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['isbn' => 'そのISBNは既に使用されています。']);

        $this->assertDatabaseCount('books', 1);
    }

    /**
     * 出版日が日付として不正なとき date エラーになること。
     */
    public function test_published_date_must_be_valid_date(): void
    {
        $this->postJson(route('api.v1.books.store'), $this->validData(['published_date' => '2012-13-45']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['published_date' => '出版日は有効な日付形式で入力してください。']);
    }

    /**
     * 画像URL が URL 形式でないとき url エラーになること。
     */
    public function test_image_url_must_be_url(): void
    {
        $this->postJson(route('api.v1.books.store'), $this->validData(['image_url' => 'not-a-url']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image_url' => '画像URLは有効なURL形式で入力してください。']);
    }

    /**
     * ジャンルが空配列のとき required エラーになること。
     */
    public function test_genres_is_required(): void
    {
        $this->postJson(route('api.v1.books.store'), $this->validData(['genres' => []]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['genres' => 'ジャンルは1つ以上選択してください。']);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 存在しないジャンル ID を送ると exists エラーになること。
     */
    public function test_genre_must_exist(): void
    {
        $this->postJson(route('api.v1.books.store'), $this->validData(['genres' => [999]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['genres.0' => '選択されたジャンルは存在しません。']);

        $this->assertDatabaseCount('books', 0);
    }
}

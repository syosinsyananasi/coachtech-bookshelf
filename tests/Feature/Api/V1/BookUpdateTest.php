<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * AP04 書籍更新API。所有者本人による更新内容の反映、ISBN 一意チェックからの自身除外、ジャンルの sync、存在しない ID で 404 になることを検証する。
 * バリデーションは Api\V1\UpdateBookRequest のルールに対応させる。
 * 認証・認可（未認証の 401、所有者以外の 403）は BookAuthorizationTest で扱う。
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
            'isbn' => '9784873115658',
        ]);
        Sanctum::actingAs($this->owner);
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
     * 書籍が更新され、200 と更新後の JSON が返ること。
     */
    public function test_book_is_updated(): void
    {
        $this->putJson(route('api.v1.books.update', $this->book), $this->validData())
            ->assertOk()
            ->assertJsonPath('data.id', $this->book->id)
            ->assertJsonPath('data.title', 'リーダブルコード 第2版')
            ->assertJsonPath('data.published_date', '2020-01-01');

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => 'リーダブルコード 第2版',
            'published_date' => '2020-01-01',
            'description' => '改訂版。',
        ]);
    }

    /**
     * 本文に user_id を送っても登録者は変更されないこと（所有権の付け替え防止）。
     */
    public function test_owner_cannot_be_changed_by_request_body(): void
    {
        $other = User::factory()->create();

        $this->putJson(route('api.v1.books.update', $this->book), $this->validData(['user_id' => $other->id]))
            ->assertOk()
            ->assertJsonPath('data.user_id', $this->owner->id);

        $this->assertDatabaseHas('books', ['id' => $this->book->id, 'user_id' => $this->owner->id]);
    }

    /**
     * ジャンルが送信内容ぴったりに置き換わること（外したジャンルは detach、新しいジャンルは attach）。
     */
    public function test_genres_are_synced(): void
    {
        $old = Genre::factory()->create();
        $new = Genre::factory()->create();
        $this->book->genres()->attach($old);

        $this->putJson(route('api.v1.books.update', $this->book), $this->validData(['genres' => [$new->id]]))
            ->assertOk()
            ->assertJsonCount(1, 'data.genres')
            ->assertJsonPath('data.genres.0.id', $new->id);

        $this->assertDatabaseMissing('book_genre', ['book_id' => $this->book->id, 'genre_id' => $old->id]);
        $this->assertDatabaseHas('book_genre', ['book_id' => $this->book->id, 'genre_id' => $new->id]);
    }

    /**
     * ISBN を変えずに更新できること（Rule::unique()->ignore() の検証）。
     */
    public function test_same_isbn_can_be_submitted(): void
    {
        $this->putJson(route('api.v1.books.update', $this->book), $this->validData(['isbn' => '9784873115658']))
            ->assertOk();
    }

    /**
     * 他の書籍と同じ ISBN にすると unique エラーになること。
     */
    public function test_isbn_must_be_unique_against_other_books(): void
    {
        Book::factory()->create(['isbn' => '9784101010014']);

        $this->putJson(route('api.v1.books.update', $this->book), $this->validData(['isbn' => '9784101010014']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['isbn' => 'そのISBNは既に使用されています。']);

        $this->assertDatabaseHas('books', ['id' => $this->book->id, 'isbn' => '9784873115658']);
    }

    /**
     * タイトルが未入力のとき required エラーになり、更新されないこと。
     */
    public function test_title_is_required(): void
    {
        $this->putJson(route('api.v1.books.update', $this->book), $this->validData(['title' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title' => 'タイトルは必須です。']);

        $this->assertDatabaseHas('books', ['id' => $this->book->id, 'title' => 'リーダブルコード']);
    }

    /**
     * 存在しない ID を指定すると 404 の JSON が返ること。
     */
    public function test_not_found_for_missing_book(): void
    {
        $this->putJson(route('api.v1.books.update', 999), $this->validData())
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }
}

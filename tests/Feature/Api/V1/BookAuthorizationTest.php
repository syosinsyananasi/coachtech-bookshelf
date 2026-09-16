<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * AP06 公開API の認証・認可（Sanctum）。
 * 認証: 登録・更新・削除は Bearer トークン無しを 401 で拒否すること（auth:sanctum）。一覧・詳細は認証不要のまま。
 * 認可: 更新・削除は所有者以外を 403 で拒否すること（BookPolicy）。
 */
class BookAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->book = Book::factory()->create(['user_id' => $this->owner->id, 'title' => '元のタイトル']);
    }

    /**
     * バリデーションを通過する入力値を返す。
     *
     * @return array<string, mixed>
     */
    private function validData(): array
    {
        return [
            'title' => 'リーダブルコード',
            'author' => 'Dustin Boswell',
            'isbn' => '9784873115658',
            'published_date' => '2012-06-23',
            'genres' => [Genre::factory()->create()->id],
        ];
    }

    // ---- 認証不要（読み取り） ----

    /**
     * 一覧と詳細はトークン無しでも 200 で取得できること。
     */
    public function test_read_endpoints_do_not_require_token(): void
    {
        $this->getJson(route('api.v1.books.index'))->assertOk();
        $this->getJson(route('api.v1.books.show', $this->book))->assertOk();
    }

    // ---- 認証（トークン無し） ----

    /**
     * トークン無しの登録リクエストが 401 で拒否され、書籍が保存されないこと。
     */
    public function test_guest_cannot_store_book(): void
    {
        $this->postJson(route('api.v1.books.store'), $this->validData())
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);

        $this->assertDatabaseCount('books', 1);
    }

    /**
     * トークン無しの更新リクエストが 401 で拒否され、書籍が変更されないこと。
     */
    public function test_guest_cannot_update_book(): void
    {
        $this->putJson(route('api.v1.books.update', $this->book), $this->validData())
            ->assertUnauthorized();

        $this->assertDatabaseHas('books', ['id' => $this->book->id, 'title' => '元のタイトル']);
    }

    /**
     * トークン無しの削除リクエストが 401 で拒否され、書籍が残ること。
     */
    public function test_guest_cannot_delete_book(): void
    {
        $this->deleteJson(route('api.v1.books.destroy', $this->book))
            ->assertUnauthorized();

        $this->assertDatabaseHas('books', ['id' => $this->book->id]);
    }

    /**
     * 無効なトークンを送っても 401 になること（トークンの照合の検証）。
     */
    public function test_invalid_token_is_rejected(): void
    {
        $this->withHeader('Authorization', 'Bearer invalid-token')
            ->postJson(route('api.v1.books.store'), $this->validData())
            ->assertUnauthorized();

        $this->assertDatabaseCount('books', 1);
    }

    // ---- 認可（所有者以外） ----

    /**
     * 所有者以外のトークンによる更新が 403 で拒否され、書籍が変更されないこと（BookPolicy::update の検証）。
     */
    public function test_other_user_cannot_update_book(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson(route('api.v1.books.update', $this->book), $this->validData())
            ->assertForbidden()
            ->assertJson(['message' => 'This action is unauthorized.']);

        $this->assertDatabaseHas('books', ['id' => $this->book->id, 'title' => '元のタイトル']);
    }

    /**
     * 所有者以外のトークンによる削除が 403 で拒否され、書籍が残ること（BookPolicy::delete の検証）。
     */
    public function test_other_user_cannot_delete_book(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson(route('api.v1.books.destroy', $this->book))
            ->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $this->book->id]);
    }

    /**
     * 認証済みなら誰でも登録できること（登録に所有者の概念は無い）。
     */
    public function test_any_authenticated_user_can_store_book(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson(route('api.v1.books.store'), $this->validData())
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id);
    }
}

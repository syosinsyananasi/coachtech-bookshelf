<?php

namespace Tests\Feature\Book;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ★ ISBN 検索（GET /books/isbn/{isbn}）。Google Books API との通信は Http::fake() で差し替え、
 * 実際には接続しない。ステータスと文言は PM 回答（Q-017-1）、複数件・著者複数の扱いは Q-017-2 に従う。
 */
class IsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    private const ISBN = '9784873115658';

    /**
     * Google Books の正常応答（2 件ヒット、先頭は著者 2 名）を返す fake を登録する。
     */
    private function fakeGoogleBooksFound(): void
    {
        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'totalItems' => 2,
                'items' => [
                    ['volumeInfo' => [
                        'title' => 'リーダブルコード',
                        'authors' => ['Dustin Boswell', 'Trevor Foucher'],
                        'publishedDate' => '2012-06-23',
                        'description' => 'より良いコードを書くためのシンプルで実践的なテクニック',
                        'imageLinks' => ['thumbnail' => 'http://books.google.com/books/content?id=xxx'],
                    ]],
                    ['volumeInfo' => ['title' => '別の登録データ']],
                ],
            ]),
        ]);
    }

    /**
     * 成功時に 200 で、フォームに流し込む 5 項目が返ること。
     * 複数件ヒットは先頭の 1 件を採用し、著者が複数のときは「、」で連結する。
     */
    public function test_book_info_is_returned_for_valid_isbn(): void
    {
        $this->fakeGoogleBooksFound();

        $this->actingAs(User::factory()->create())
            ->getJson(route('books.isbn', self::ISBN))
            ->assertOk()
            ->assertExactJson([
                'title' => 'リーダブルコード',
                'author' => 'Dustin Boswell、Trevor Foucher',
                'published_date' => '2012-06-23',
                'description' => 'より良いコードを書くためのシンプルで実践的なテクニック',
                'image_url' => 'http://books.google.com/books/content?id=xxx',
            ]);
    }

    /**
     * Google Books へ isbn: 付きの検索語で問い合わせること（サーバーから送る問い合わせの中身の検証。Http::fake() が記録した送信内容を assertSent で確認する）。
     */
    public function test_google_books_is_queried_with_isbn(): void
    {
        $this->fakeGoogleBooksFound();

        $this->actingAs(User::factory()->create())
            ->getJson(route('books.isbn', self::ISBN));

        Http::assertSent(fn (Request $request) => $request['q'] === 'isbn:'.self::ISBN);
    }

    /**
     * Google の応答に無い項目は null で返ること（画面側は空欄のままにする）。
     */
    public function test_missing_fields_are_returned_as_null(): void
    {
        Http::fake(['www.googleapis.com/*' => Http::response(['totalItems' => 1, 'items' => [['volumeInfo' => ['title' => 'タイトルのみ']]]])]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('books.isbn', self::ISBN))
            ->assertOk()
            ->assertExactJson(['title' => 'タイトルのみ', 'author' => null, 'published_date' => null, 'description' => null, 'image_url' => null]);
    }

    /**
     * 該当する書籍が無いとき 404 と {"error": "書籍が見つかりませんでした。"} を返すこと。
     */
    public function test_not_found_returns404(): void
    {
        Http::fake(['www.googleapis.com/*' => Http::response(['totalItems' => 0])]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('books.isbn', self::ISBN))
            ->assertNotFound()
            ->assertExactJson(['error' => '書籍が見つかりませんでした。']);
    }

    /**
     * Google 側がエラー（上限超過の 429 など）を返したとき 500 と {"error": "API通信エラーが発生しました。"} を返すこと。
     * failed() を先に判定しないと「該当なし」と誤認するため、本文に items が無い応答で検証する。
     */
    public function test_google_error_returns500(): void
    {
        Http::fake(['www.googleapis.com/*' => Http::response(['error' => ['code' => 429]], 429)]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('books.isbn', self::ISBN))
            ->assertStatus(500)
            ->assertExactJson(['error' => 'API通信エラーが発生しました。']);
    }

    /**
     * タイムアウト・接続失敗のとき 500 と {"error": "API通信エラーが発生しました。"} を返すこと。
     */
    public function test_connection_failure_returns500(): void
    {
        Http::fake(fn () => throw new ConnectionException('timed out'));

        $this->actingAs(User::factory()->create())
            ->getJson(route('books.isbn', self::ISBN))
            ->assertStatus(500)
            ->assertExactJson(['error' => 'API通信エラーが発生しました。']);
    }

    /**
     * 13 桁でない ISBN は 400 と {"error": "ISBNは13桁で入力してください。"} を返し、Google には問い合わせないこと。
     */
    public function test_invalid_isbn_returns400_without_calling_google(): void
    {
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->getJson(route('books.isbn', '123'))
            ->assertStatus(400)
            ->assertExactJson(['error' => 'ISBNは13桁で入力してください。']);

        Http::assertNothingSent();
    }

    /**
     * 未認証で画面の JavaScript と同じ JSON 要求をすると 401 になること。
     */
    public function test_guest_json_request_is_unauthorized(): void
    {
        $this->getJson(route('books.isbn', self::ISBN))
            ->assertUnauthorized();
    }

    /**
     * 未認証で URL を直接開くとログイン画面へリダイレクトされること。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('books.isbn', self::ISBN))
            ->assertRedirect(route('login'));
    }
}

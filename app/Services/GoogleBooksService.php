<?php

namespace App\Services;

use App\Exceptions\GoogleBooksException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Google Books API から ISBN で書籍情報を取得するサービス（★応用 ISBN 検索）。
 *
 * Laravel 標準の HTTP クライアント（Http ファサード）を使う。タイムアウトは 5 秒。
 * 返す項目は、書籍登録・編集画面の JavaScript がフォームに流し込む 5 つに合わせる（PM 回答 Q-017-1）。
 */
class GoogleBooksService
{
    /**
     * ISBN に該当する書籍情報を 1 件返す。
     *
     * 複数件ヒットした場合は先頭の 1 件を採用し、著者が複数の場合は「、」で連結する（PM 回答 Q-017-2 により自身の判断で確定）。
     * 出版日・説明・画像 URL は Google の値をそのまま返し、無い項目は null にする。
     *
     * @param  string  $isbn  13 桁の ISBN
     * @return array{title: ?string, author: ?string, published_date: ?string, description: ?string, image_url: ?string}|null 該当なしは null
     *
     * @throws GoogleBooksException タイムアウト・接続失敗・Google 側のエラー
     */
    public function findByIsbn(string $isbn): ?array
    {
        $query = collect(['q' => "isbn:{$isbn}", 'key' => config('services.google_books.key')])->filter();

        try {
            $response = Http::timeout(5)->get(config('services.google_books.url'), $query->all());
        } catch (ConnectionException $e) {
            throw new GoogleBooksException('Google Books API に接続できませんでした。', 0, $e);
        }

        if ($response->failed()) {
            throw new GoogleBooksException("Google Books API がエラーを返しました（HTTP {$response->status()}）。");
        }

        $volume = $response->json('items.0.volumeInfo');

        if ($volume === null) {
            return null;
        }

        return [
            'title' => $volume['title'] ?? null,
            'author' => collect($volume['authors'] ?? [])->implode('、') ?: null,
            'published_date' => $volume['publishedDate'] ?? null,
            'description' => $volume['description'] ?? null,
            'image_url' => $volume['imageLinks']['thumbnail'] ?? null,
        ];
    }
}

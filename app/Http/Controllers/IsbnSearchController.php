<?php

namespace App\Http\Controllers;

use App\Exceptions\GoogleBooksException;
use App\Http\Requests\IsbnSearchRequest;
use App\Services\GoogleBooksService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * ★ ISBN 検索を担当するコントローラ（書籍登録・編集画面の「ISBN検索」ボタン）。
 *
 * 画面は切り替わらず、JavaScript が GET /books/isbn/{isbn} を呼んで返った JSON をフォームに流し込む。
 * 呼び出し元の画面が認証必須のため、このエンドポイントも auth ミドルウェアの下に置く。
 * エラーはすべて {"error": "…"} 形式で返す（JavaScript が error キーの有無で成否を判定するため）。
 */
class IsbnSearchController extends Controller
{
    /**
     * ISBN で Google Books を検索し、フォームに流し込む書籍情報を返す。
     *
     * ステータスと文言は PM 回答（Q-017-1）のとおり。
     * 13 桁以外は IsbnSearchRequest が 400 を返す。該当なしは 404、Google Books との通信に失敗したときは 500。
     *
     * @param  IsbnSearchRequest  $request  バリデーション済みの ISBN
     * @param  GoogleBooksService  $googleBooks  Google Books API との通信
     * @return JsonResponse title / author / published_date / description / image_url
     */
    public function search(IsbnSearchRequest $request, GoogleBooksService $googleBooks): JsonResponse
    {
        try {
            $book = $googleBooks->findByIsbn($request->validated('isbn'));
        } catch (GoogleBooksException $e) {
            report($e);

            return response()->json(['error' => 'API通信エラーが発生しました。'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($book === null) {
            return response()->json(['error' => '書籍が見つかりませんでした。'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($book);
    }
}

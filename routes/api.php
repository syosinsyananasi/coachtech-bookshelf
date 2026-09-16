<?php

use App\Http\Controllers\Api\V1\BookController;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// 存在しない書籍 ID が指定されたときの応答（PM 確認済み: API では {"error": "書籍が見つかりませんでした。"} を 404 で返す）。
// Web 側は通常の 404 画面のままとする。
$bookNotFound = fn () => response()->json(['error' => '書籍が見つかりませんでした。'], Response::HTTP_NOT_FOUND);

// 読み取り系（一覧・詳細）は認証不要
Route::apiResource('v1/books', BookController::class)
    ->only(['index', 'show'])
    ->names('api.v1.books')
    ->missing($bookNotFound);

// 書き込み系（登録・更新・削除）は Sanctum の Bearer トークン認証を必須とする（未認証は 401）。
// 更新・削除の所有者チェックはコントローラで BookPolicy を適用する（所有者以外は 403）。
Route::apiResource('v1/books', BookController::class)
    ->only(['store', 'update', 'destroy'])
    ->names('api.v1.books')
    ->middleware('auth:sanctum')
    ->missing($bookNotFound);

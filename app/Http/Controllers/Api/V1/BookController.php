<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * 公開API 書籍の CRUD を担当するコントローラ（AP01〜AP05）。
 *
 * 一覧・詳細は認証なし。登録・更新・削除は Sanctum の Bearer トークン認証を必須とし（routes/api.php の auth:sanctum）、
 * 登録者はトークンの持ち主、更新・削除は BookPolicy で所有者本人のみ許可する。
 * レスポンスは BookResource で整形し、成功時のステータスは登録 201・更新 200・削除 204 を返す。
 * 未認証は 401、他人の書籍への操作は 403 となる。
 */
class BookController extends Controller
{
    /**
     * 書籍一覧を取得する。
     *
     * keyword はタイトル・著者名の部分一致、genre_id はジャンルによる絞り込み。
     * 各書籍にジャンル・平均評価・レビュー件数を含めるため、genres の Eager Loading と
     * withAvg / withCount を使う。検索条件はページネーションのリンクに引き継ぐ。
     *
     * @param  IndexBookRequest  $request  バリデーション済みの検索パラメータ
     * @return AnonymousResourceCollection 書籍一覧（ページネーション付き）
     */
    public function index(IndexBookRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($validated['keyword'] ?? null, fn (Builder $query, string $keyword) => $query->whereAny(['title', 'author'], 'like', "%{$keyword}%"))
            ->when($validated['genre_id'] ?? null, fn (Builder $query, int $genreId) => $query->whereRelation('genres', 'genres.id', $genreId))
            ->latest()
            ->paginate($validated['per_page'] ?? 20)
            ->appends($validated);

        return BookResource::collection($books);
    }

    /**
     * 書籍を新規登録する。
     *
     * 登録者は Sanctum トークンの持ち主（$request->user()）とし、そのリレーション経由で作成することで
     * user_id を設定する（Book の $fillable に user_id を含めないため）。ジャンルは保存後に中間テーブルへ sync する。
     *
     * @param  StoreBookRequest  $request  バリデーション済みの書籍情報・ジャンルID
     * @return JsonResponse 登録した書籍（201 Created）
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $book = $request->user()->books()->create($validated);
        $book->genres()->sync($validated['genres']);

        return (new BookResource($this->withDetails($book)))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * 書籍詳細を取得する。
     *
     * ジャンルとレビュー（投稿者名付き）を含める。存在しない ID はルートモデルバインディングにより 404 となる。
     *
     * @param  Book  $book  取得対象の書籍
     * @return BookResource 書籍詳細
     */
    public function show(Book $book): BookResource
    {
        return new BookResource($this->withDetails($book->load('reviews.user')));
    }

    /**
     * 書籍を更新する。
     *
     * 所有者本人のみ許可するため BookPolicy::update で認可する（トークンの持ち主と書籍の user_id を比較）。
     * 登録者は変更しない。ジャンルは sync() で送信された内容に置き換える。
     *
     * @param  UpdateBookRequest  $request  バリデーション済みの書籍情報・ジャンルID
     * @param  Book  $book  更新対象の書籍
     * @return BookResource 更新後の書籍
     */
    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $book->update($validated);
        $book->genres()->sync($validated['genres']);

        return new BookResource($this->withDetails($book));
    }

    /**
     * 書籍を削除する。
     *
     * 所有者本人のみ許可するため BookPolicy::delete で認可する。
     * 関連するレビュー・お気に入り・ジャンル紐付けは外部キーの onDelete('cascade') により自動で削除される。
     *
     * @param  Book  $book  削除対象の書籍
     * @return Response 本文なし（204 No Content）
     */
    public function destroy(Book $book): Response
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->noContent();
    }

    /**
     * レスポンスに必要なジャンル・平均評価・レビュー件数を書籍に読み込む。
     *
     * 単一の書籍を返す store / show / update で共通して使う。
     *
     * @param  Book  $book  対象の書籍
     * @return Book リレーションと集計値を読み込んだ書籍
     */
    private function withDetails(Book $book): Book
    {
        return $book->load('genres')->loadAvg('reviews', 'rating')->loadCount('reviews');
    }
}

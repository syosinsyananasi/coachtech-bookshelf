<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 書籍の CRUD を担当するコントローラ（PG01〜PG04）。
 *
 * 一覧・詳細は公開ページ、登録・編集・削除は認証必須。
 * 編集・削除は作成者本人のみ許可するため BookPolicy による認可を行う。
 */
class BookController extends Controller
{
    /**
     * 書籍一覧を表示する。
     *
     * 最新の登録順に 10件/ページ で取得する。ビューが各書籍のジャンルを描画するため、
     * N+1 を避けて genres を Eager Loading している。
     *
     * @return View 書籍一覧画面
     */
    public function index(): View
    {
        $books = Book::with('genres')->latest()->paginate(10);

        return view('books.index', compact('books'));
    }

    /**
     * 書籍登録フォームを表示する。
     *
     * ジャンル選択のチェックボックスを描画するため、全ジャンルを渡す。
     *
     * @return View 書籍登録画面
     */
    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * 書籍を新規登録する。
     *
     * ログインユーザーのリレーション経由で作成することで user_id を自動設定する。
     * ジャンルは books テーブルのカラムではないため、保存後に中間テーブルへ sync する。
     *
     * @param  StoreBookRequest  $request  バリデーション済みの書籍情報とジャンルID
     * @return RedirectResponse 登録した書籍の詳細へのリダイレクト（成功メッセージ付き）
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $book = $user->books()->create($validated);
        $book->genres()->sync($validated['genres']);

        return redirect()->route('books.show', $book)->with('success', '書籍を登録しました。');
    }

    /**
     * 書籍詳細を表示する。
     *
     * ルートモデルバインディングで取得済みのため、リレーションは load() で後から読み込む。
     * ビューがレビューごとに投稿者名といいね件数を描画するため、
     * N+1 を避けて reviews.user と reviews.likedByUsers もまとめて Eager Loading している。
     *
     * @param  Book  $book  表示対象の書籍
     * @return View 書籍詳細画面
     */
    public function show(Book $book): View
    {
        $book->load(['genres', 'reviews.user', 'reviews.likedByUsers']);

        return view('books.show', compact('book'));
    }

    /**
     * 書籍編集フォームを表示する。
     *
     * 既存のジャンルにチェックを付けるため書籍の genres を、
     * 選択肢を描画するため全ジャンルを渡す。
     *
     * @param  Book  $book  編集対象の書籍
     * @return View 書籍編集画面
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $book->load('genres');
        $genres = Genre::all();

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍を更新する。
     *
     * ジャンルは sync() で送信された内容ぴったりに置き換える（差分は自動で処理される）。
     *
     * @param  UpdateBookRequest  $request  バリデーション済みの書籍情報とジャンルID
     * @param  Book  $book  更新対象の書籍
     * @return RedirectResponse 更新した書籍の詳細へのリダイレクト（成功メッセージ付き）
     */
    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $book->update($validated);
        $book->genres()->sync($validated['genres']);

        return redirect()->route('books.show', $book)->with('success', '書籍を更新しました。');
    }

    /**
     * 書籍を削除する。
     *
     * 中間テーブル book_genre は外部キーの onDelete('cascade') により自動で削除される。
     * 関連するレビュー（reviews）とお気に入り（favorites）も同様にカスケード削除される。
     *
     * @param  Book  $book  削除対象の書籍
     * @return RedirectResponse 書籍一覧へのリダイレクト（成功メッセージ付き）
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()->route('books.index')->with('success', '書籍を削除しました。');
    }
}

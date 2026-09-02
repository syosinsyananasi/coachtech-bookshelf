<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * ジャンルの CRUD を担当するコントローラ（PG05〜PG08）。
 *
 * 全アクションが認証必須で、認可要件は無いため Policy は用いず
 * routes/web.php の auth ミドルウェアで担保している。
 */
class GenreController extends Controller
{
    /**
     * ジャンル一覧を表示する。
     *
     * ビューが各ジャンルの書籍数を参照するため、withCount で books_count を付与する。
     *
     * @return View ジャンル一覧画面
     */
    public function index(): View
    {
        $genres = Genre::withCount('books')->get();

        return view('genres.index', compact('genres'));
    }

    /**
     * ジャンル登録フォームを表示する。
     *
     * @return View ジャンル登録画面
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * ジャンルを新規登録する。
     *
     * @param  StoreGenreRequest  $request  バリデーション済みのジャンル名
     * @return RedirectResponse ジャンル一覧へのリダイレクト（成功メッセージ付き）
     */
    public function store(StoreGenreRequest $request): RedirectResponse
    {
        Genre::create($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを作成しました。');
    }

    /**
     * ジャンル詳細を表示する。
     *
     * 紐づく書籍を 10件/ページ で取得する。ビューが各書籍のジャンルを描画するため、
     * N+1 を避けて genres を Eager Loading している。
     *
     * @param  Genre  $genre  表示対象のジャンル
     * @return View ジャンル詳細画面
     */
    public function show(Genre $genre): View
    {
        $books = $genre->books()->with('genres')->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    /**
     * ジャンル編集フォームを表示する。
     *
     * @param  Genre  $genre  編集対象のジャンル
     * @return View ジャンル編集画面
     */
    public function edit(Genre $genre): View
    {
        return view('genres.edit', compact('genre'));
    }

    /**
     * ジャンル名を更新する。
     *
     * @param  UpdateGenreRequest  $request  バリデーション済みのジャンル名
     * @param  Genre  $genre  更新対象のジャンル
     * @return RedirectResponse ジャンル一覧へのリダイレクト（成功メッセージ付き）
     */
    public function update(UpdateGenreRequest $request, Genre $genre): RedirectResponse
    {
        $genre->update($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを更新しました。');
    }

    /**
     * ジャンルを削除する。
     *
     * 書籍が1件でも紐づいている場合は削除せず、エラーメッセージを返す（削除制限の要件）。
     *
     * @param  Genre  $genre  削除対象のジャンル
     * @return RedirectResponse ジャンル一覧へのリダイレクト（成功／エラーメッセージ付き）
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        // 中間テーブルを確認して使用されているものがあれば削除できない
        if ($genre->books()->exists()) {
            return redirect()->route('genres.index')->with('error', 'このジャンルには書籍が紐付いているため削除できません。');
        }
        $genre->delete();

        return redirect()->route('genres.index')->with('success', 'ジャンルを削除しました。');
    }
}

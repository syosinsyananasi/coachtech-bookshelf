<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * お気に入りの一覧表示とトグルを担当するコントローラ（PG02・PG10）。
 *
 * すべて認証必須。ログインユーザー自身のお気に入りのみを扱うため、Policy による認可は行わない。
 * 中間テーブル favorites を User::favoriteBooks() 経由で操作する。
 */
class FavoriteController extends Controller
{
    /**
     * お気に入り一覧を表示する。
     *
     * ログインユーザーのお気に入り書籍を 10件/ページ で取得する。
     * ビューが $books を参照するため、変数名を合わせて渡す。
     *
     * @return View お気に入り一覧画面
     */
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $books = $user->favoriteBooks()->latest()->paginate(10);

        return view('favorites.index', compact('books'));
    }

    /**
     * 書籍のお気に入りを切り替える。
     *
     * 未登録なら追加、登録済みなら解除する（トグル）。
     * 書籍詳細と一覧の両方から呼ばれるため、リダイレクト先は直前の画面とする。
     *
     * @param  Book  $book  お気に入り対象の書籍
     * @return RedirectResponse 直前の画面へのリダイレクト
     */
    public function toggle(Book $book): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $user->favoriteBooks()->toggle($book->id);

        return back();
    }
}

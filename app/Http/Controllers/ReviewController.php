<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * レビューの投稿・編集・削除・いいねを担当するコントローラ（PG02・PG09）。
 *
 * すべて認証必須。編集・削除は投稿者本人のみ、いいねは投稿者本人以外のみ許可するため ReviewPolicy による認可を行う。
 * レビューは書籍詳細画面に表示されるため、各処理後は書籍詳細へリダイレクトする。
 */
class ReviewController extends Controller
{
    /**
     * レビューを新規投稿する。
     *
     * 対象書籍はルートモデルバインディングで受け取り、reviews() 経由で作成することで book_id を自動設定する。
     * 投稿者はログインユーザーとし、user_id を明示的に渡す。
     *
     * @param  StoreReviewRequest  $request  バリデーション済みの評価とコメント
     * @param  Book  $book  レビュー対象の書籍
     * @return RedirectResponse 書籍詳細へのリダイレクト（成功メッセージ付き）
     */
    public function store(StoreReviewRequest $request, Book $book): RedirectResponse
    {
        $validated = $request->validated();
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $book->reviews()->create([...$validated, 'user_id' => $user->id]);

        return redirect()->route('books.show', $book)->with('success', 'レビューを投稿しました。');
    }

    /**
     * レビュー編集フォームを表示する。
     *
     * 既存の評価・コメントを初期値として表示するため、対象レビューをビューに渡す。
     *
     * @param  Review  $review  編集対象のレビュー
     * @return View レビュー編集画面
     */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    /**
     * レビューを更新する。
     *
     * @param  UpdateReviewRequest  $request  バリデーション済みの評価とコメント
     * @param  Review  $review  更新対象のレビュー
     * @return RedirectResponse 書籍詳細へのリダイレクト（成功メッセージ付き）
     */
    public function update(UpdateReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);
        $validated = $request->validated();
        $review->update($validated);

        return redirect()->route('books.show', $review->book_id)->with('success', 'レビューを更新しました。');
    }

    /**
     * レビューを削除する。
     *
     * 関連するいいね（review_likes）は外部キーの onDelete('cascade') により自動で削除される。
     *
     * @param  Review  $review  削除対象のレビュー
     * @return RedirectResponse 書籍詳細へのリダイレクト（成功メッセージ付き）
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);
        $review->delete();

        return redirect()->route('books.show', $review->book_id)->with('success', 'レビューを削除しました。');
    }

    /**
     * レビューへのいいねを切り替える。
     *
     * 未登録なら追加、登録済みなら解除する（トグル）。
     * 自分のレビューへのいいねは ReviewPolicy で拒否する。
     * 中間テーブル review_likes を likedByUsers() 経由で操作するため、FormRequest は不要。
     *
     * @param  Review  $review  いいね対象のレビュー
     * @return RedirectResponse 書籍詳細へのリダイレクト
     */
    public function like(Review $review): RedirectResponse
    {
        $this->authorize('like', $review);

        $review->likedByUsers()->toggle(auth()->id());

        return redirect()->route('books.show', $review->book_id);
    }
}

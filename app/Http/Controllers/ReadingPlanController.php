<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\ReadingPlanIndexRequest;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 読書計画の画面を担当するコントローラ（★PG15〜PG17）。
 *
 * すべて認証必須。表示・操作できるのはログインユーザー自身の計画だけで、
 * 編集・読了・削除は ReadingPlanPolicy で所有者本人のみ許可する。
 */
class ReadingPlanController extends Controller
{
    /**
     * 読書計画一覧を表示する。
     *
     * ログインユーザーの計画を期日の近い順に表示し、status が指定されていればその状態だけに絞る。
     * 各行が書籍名を描画するため book を Eager Loading する。
     *
     * @param  ReadingPlanIndexRequest  $request  バリデーション済みの絞り込みパラメータ
     * @return View 読書計画一覧画面
     */
    public function index(ReadingPlanIndexRequest $request): View
    {
        $validated = $request->validated();
        $currentStatus = $validated['status'] ?? '';

        $readingPlans = ReadingPlan::whereBelongsTo($request->user())
            ->with('book')
            ->when($validated['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('target_date')
            ->orderBy('id')
            ->get();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * 読書計画作成フォームを表示する。
     *
     * 書籍プルダウンを描画するため、全書籍をタイトル順で渡す。
     *
     * @return View 読書計画作成画面
     */
    public function create(): View
    {
        $books = Book::orderBy('title')->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 読書計画を新規登録する。
     *
     * ログインユーザーのリレーション経由で作成することで user_id を自動設定する。
     * status は DB の既定値（in_progress）、completed_at は null のまま。
     *
     * @param  StoreReadingPlanRequest  $request  バリデーション済みの書籍 ID と期日
     * @return RedirectResponse 一覧へのリダイレクト（成功メッセージ付き）
     */
    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        $request->user()->readingPlans()->create($request->validated());

        return redirect()->route('reading-plans.index')->with('success', '読書計画を作成しました。');
    }

    /**
     * 読書計画編集フォームを表示する。
     *
     * 所有者本人かつ完了済みでない場合のみ許可する（ReadingPlanPolicy::update）。
     * Blade が書籍名と現在の状態を描画するため book を読み込む。
     *
     * @param  ReadingPlan  $readingPlan  編集対象の計画
     * @return View 読書計画編集画面
     */
    public function edit(ReadingPlan $readingPlan): View
    {
        $this->authorize('update', $readingPlan);

        $readingPlan->load('book');

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画の期日を更新する。
     *
     * 所有者本人かつ完了済みでない場合のみ許可する。期限切れの計画は、期日が今日以降に変わるので進行中に戻す
     * （期日が未来なのに期限切れのままでは矛盾するため）。
     *
     * @param  UpdateReadingPlanRequest  $request  バリデーション済みの期日
     * @param  ReadingPlan  $readingPlan  更新対象の計画
     * @return RedirectResponse 一覧へのリダイレクト（成功メッセージ付き）
     */
    public function update(UpdateReadingPlanRequest $request, ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('update', $readingPlan);

        $readingPlan->update([
            ...$request->validated(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を更新しました。');
    }

    /**
     * 読書計画を読了（完了）にする。
     *
     * 所有者本人かつ完了済みでない場合のみ許可する（ReadingPlanPolicy::complete）。
     * status を completed にし、completed_at に現在日時を入れる。
     *
     * @param  ReadingPlan  $readingPlan  読了にする計画
     * @return RedirectResponse 一覧へのリダイレクト（成功メッセージ付き。PM 回答 Q-006-1 の文言）
     */
    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('complete', $readingPlan);

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を完了しました。');
    }

    /**
     * 読書計画を削除する。
     *
     * 所有者本人のみ許可する（ReadingPlanPolicy::delete）。状態は問わない。
     *
     * @param  ReadingPlan  $readingPlan  削除対象の計画
     * @return RedirectResponse 一覧へのリダイレクト（成功メッセージ付き）
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました。');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReadingPlanIndexRequest;
use App\Models\ReadingPlan;
use Illuminate\Database\Eloquent\Builder;
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
}

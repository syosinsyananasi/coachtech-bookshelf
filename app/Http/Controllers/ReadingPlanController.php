<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 読書計画の画面を担当するコントローラ（★PG15〜PG17）。
 *
 * Advanced ブランチの Blade 取り込み時点の骨組み。一覧は空の状態で表示するだけで、
 * 計画の取得・作成・更新・読了・削除は読書計画の機能ブランチで実装する。
 */
class ReadingPlanController extends Controller
{
    /**
     * 読書計画一覧を表示する（骨組み）。
     *
     * @return View 読書計画一覧画面
     */
    public function index(Request $request): View
    {
        $readingPlans = collect();
        $currentStatus = (string) $request->query('status', '');

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }
}

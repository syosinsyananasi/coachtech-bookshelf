<?php

namespace App\Http\Controllers;

use App\Services\ReadingReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * マイ読書レポートの画面を担当するコントローラ（★PG14）。
 *
 * 認証必須。集計はログインユーザー自身のレビューと読書計画だけを対象にする。
 */
class ReportController extends Controller
{
    /**
     * マイ読書レポートを表示する。
     *
     * 集計は ReadingReportService に任せ、コントローラは受け取った結果をビューに渡すだけにする。
     *
     * @param  ReadingReportService  $report  レポートの集計
     * @return View マイ読書レポート画面
     */
    public function index(Request $request, ReadingReportService $report): View
    {
        $stats = $report->build($request->user());

        return view('reports.index', compact('stats'));
    }
}

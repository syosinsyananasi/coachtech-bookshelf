<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * マイ読書レポートの画面を担当するコントローラ（★PG14）。
 *
 * Advanced ブランチの Blade 取り込み時点の骨組み。統計はすべて 0 件の状態で表示するだけで、
 * 集計はマイ読書レポートの機能ブランチで実装する。
 */
class ReportController extends Controller
{
    /**
     * マイ読書レポートを表示する（骨組み）。
     *
     * Blade が参照するキー（summary / rating_distribution / top_rated_books / genre_ratings）を空の値で渡す。
     *
     * @return View マイ読書レポート画面
     */
    public function index(): View
    {
        $stats = [
            'summary' => ['total_reviews' => 0, 'books_read' => 0, 'average_rating' => 0],
            'rating_distribution' => collect([0, 0, 0, 0, 0]),
            'top_rated_books' => [],
            'genre_ratings' => [],
        ];

        return view('reports.index', compact('stats'));
    }
}

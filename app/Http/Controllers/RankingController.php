<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\View\View;

/**
 * 評価ランキングの表示を担当するコントローラ（PG11）。
 *
 * 公開ページのため認証・認可は行わない。
 */
class RankingController extends Controller
{
    /**
     * レビュー平均評価の TOP10 を表示する。
     *
     * レビューが無い書籍は has() で除外し、平均評価と件数は withAvg / withCount で SQL 側に集計させる。
     * 並び替えと件数制限も SQL 側で行い、書籍数に関係なく 10件 だけ取得する。
     * 平均評価が同点の場合は ID の大きい順（登録が新しい順）に並べる（PM 回答 Q-028-2 で判断を一任）。
     * ビューが reviews_avg_rating と reviews_count を参照するため、属性名は Laravel の自動命名に合わせる。
     *
     * @return View ランキング画面
     */
    public function index(): View
    {
        $rankedBooks = Book::has('reviews')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('id')
            ->take(10)
            ->get();

        return view('ranking.index', compact('rankedBooks'));
    }
}

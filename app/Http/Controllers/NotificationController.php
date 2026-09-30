<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * 通知の画面を担当するコントローラ（★PG18）。
 *
 * Advanced ブランチの Blade 取り込み時点の骨組み。一覧は空の状態で表示するだけで、
 * 通知の取得・既読化は通知の機能ブランチで実装する。
 */
class NotificationController extends Controller
{
    /**
     * 通知一覧を表示する（骨組み）。
     *
     * @return View 通知一覧画面
     */
    public function index(): View
    {
        $notifications = collect();

        return view('notifications.index', compact('notifications'));
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

/**
 * 通知の画面を担当するコントローラ（★PG18）。
 *
 * 認証必須。表示するのはログインユーザー宛ての通知だけで、既読化は NotificationPolicy で受信者本人のみ許可する。
 */
class NotificationController extends Controller
{
    /**
     * 通知一覧を表示する。
     *
     * ログインユーザー宛ての通知を新しい順に表示する（Notifiable の notifications() は created_at 降順）。
     *
     * @return View 通知一覧画面
     */
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->get();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 通知を既読にする。
     *
     * 受信者本人のみ許可する（NotificationPolicy::update）。read_at に現在日時を入れ、
     * 一覧へ戻って「通知を既読にしました。」を表示する（PM 回答 Q-007-3）。
     *
     * @param  DatabaseNotification  $notification  既読にする通知
     * @return RedirectResponse 一覧へのリダイレクト（成功メッセージ付き）
     */
    public function read(DatabaseNotification $notification): RedirectResponse
    {
        $this->authorize('update', $notification);

        $notification->markAsRead();

        return redirect()->route('notifications.index')->with('success', '通知を既読にしました。');
    }
}

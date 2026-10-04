<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

/**
 * 通知の認可（★応用）。
 *
 * 対象は Laravel 標準の DatabaseNotification。自作モデルではないため命名規約で自動登録されず、
 * AuthServiceProvider の $policies で結び付ける。
 * 既読化は受信者本人のみ許可し、他ユーザーの通知 ID を指定した場合は 403（PM 回答 Q-019）。
 */
class NotificationPolicy
{
    /**
     * ユーザーが対象の通知を既読にできるか判定する。
     *
     * @param  User  $user  ログイン中のユーザー
     * @param  DatabaseNotification  $notification  対象の通知
     * @return bool 受信者本人なら true
     */
    public function update(User $user, DatabaseNotification $notification): bool
    {
        return $notification->notifiable_type === User::class && (int) $notification->notifiable_id === $user->id;
    }
}

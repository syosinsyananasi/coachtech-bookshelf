<?php

namespace App\Policies;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;

/**
 * 読書計画の認可（★応用）。
 *
 * 編集・読了・削除はすべて所有者本人のみ。編集と読了は完了済みの計画には行えない（PM 回答: 「所有者 かつ completed でない」で 403）。
 * 一覧 Blade も完了済みの行には「読了する」「編集」を出さないが、URL 直打ち対策としてここで判定する。
 */
class ReadingPlanPolicy
{
    /**
     * ユーザーが対象の計画を編集（期日変更）できるか判定する。
     *
     * 編集フォームの表示（edit）にも同じ判定を使う。
     *
     * @param  User  $user  ログイン中のユーザー
     * @param  ReadingPlan  $readingPlan  対象の計画
     * @return bool 所有者本人で、かつ完了済みでなければ true
     */
    public function update(User $user, ReadingPlan $readingPlan): bool
    {
        return $this->isOwner($user, $readingPlan) && $readingPlan->status !== ReadingPlanStatus::Completed;
    }

    /**
     * ユーザーが対象の計画を読了（完了）にできるか判定する。
     *
     * 期限切れの計画も読了にできる（Blade は完了済み以外に「読了する」を表示する）。
     *
     * @param  User  $user  ログイン中のユーザー
     * @param  ReadingPlan  $readingPlan  対象の計画
     * @return bool 所有者本人で、かつ完了済みでなければ true
     */
    public function complete(User $user, ReadingPlan $readingPlan): bool
    {
        return $this->isOwner($user, $readingPlan) && $readingPlan->status !== ReadingPlanStatus::Completed;
    }

    /**
     * ユーザーが対象の計画を削除できるか判定する。
     *
     * 状態は問わない（完了済みも削除できる）。
     *
     * @param  User  $user  ログイン中のユーザー
     * @param  ReadingPlan  $readingPlan  対象の計画
     * @return bool 所有者本人なら true
     */
    public function delete(User $user, ReadingPlan $readingPlan): bool
    {
        return $this->isOwner($user, $readingPlan);
    }

    /**
     * ログインユーザーが計画の所有者かどうかを返す。
     */
    private function isOwner(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }
}

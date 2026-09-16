<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

/**
 * レビューの認可判定を担当するポリシー（PG02・PG09）。
 *
 * 編集・削除は投稿者本人のみ、いいねは投稿者本人以外のみ許可する。
 * 閲覧・投稿は誰でも可能なため Policy の判定対象にしていない。
 * ReviewController の authorize() と書籍詳細画面の @can から参照される。
 */
class ReviewPolicy
{
    /**
     * ユーザーが対象のレビューを更新できるか判定する。
     *
     * 投稿者本人のみ許可する。編集フォームの表示（edit）にも同じ判定を使う。
     *
     * @param  User  $user  ログイン中のユーザー
     * @param  Review  $review  更新対象のレビュー
     * @return bool 投稿者本人なら true
     */
    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    /**
     * ユーザーが対象のレビューを削除できるか判定する。
     *
     * 投稿者本人のみ許可する。
     *
     * @param  User  $user  ログイン中のユーザー
     * @param  Review  $review  削除対象のレビュー
     * @return bool 投稿者本人なら true
     */
    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    /**
     * ユーザーが対象のレビューにいいねできるか判定する。
     *
     * 自分のレビューへのいいねは許可しない。提供済み Blade はボタンを出し分けないため、
     * 投稿者本人が押した場合はコントローラの authorize() で 403 となる。
     *
     * @param  User  $user  ログイン中のユーザー
     * @param  Review  $review  いいね対象のレビュー
     * @return bool 投稿者本人でなければ true
     */
    public function like(User $user, Review $review): bool
    {
        return $user->id !== $review->user_id;
    }
}

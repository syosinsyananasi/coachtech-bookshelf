<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

/**
 * レビューの認可判定を担当するポリシー（PG02・PG09）。
 *
 * 閲覧・投稿は認証済みなら誰でも可能、編集・削除は投稿者本人のみ許可する。
 * ReviewController の authorize() と書籍詳細画面の @can から参照される。
 */
class ReviewPolicy
{
    /**
     * ユーザーがレビュー一覧を閲覧できるか判定する。
     *
     * レビューは書籍詳細（公開ページ）に表示されるため、常に許可する。
     *
     * @param  User  $user  ログイン中のユーザー
     * @return bool 常に true
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * ユーザーが対象のレビューを閲覧できるか判定する。
     *
     * レビューは書籍詳細（公開ページ）に表示されるため、常に許可する。
     *
     * @param  User  $user  ログイン中のユーザー
     * @param  Review  $review  閲覧対象のレビュー
     * @return bool 常に true
     */
    public function view(User $user, Review $review): bool
    {
        return true;
    }

    /**
     * ユーザーがレビューを投稿できるか判定する。
     *
     * 認証済みなら誰でも投稿できるため、常に許可する。
     *
     * @param  User  $user  ログイン中のユーザー
     * @return bool 常に true
     */
    public function create(User $user): bool
    {
        return true;
    }

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
}

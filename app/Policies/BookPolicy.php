<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /**
     * ユーザーが書籍一覧を閲覧できるか判定する。
     *
     * 一覧は公開ページのため、常に許可する。
     *
     * @param  User  $user  ログイン中のユーザー
     * @return bool 常に true
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * ユーザーが対象の書籍を閲覧できるか判定する。
     *
     * 詳細は公開ページのため、常に許可する。
     *
     * @param  User  $user  ログイン中のユーザー
     * @param  Book  $book  閲覧対象の書籍
     * @return bool 常に true
     */
    public function view(User $user, Book $book): bool
    {
        return true;
    }

    /**
     * ユーザーが書籍を登録できるか判定する。
     *
     * 認証済みなら誰でも登録できるため、常に許可する。
     *
     * @param  User  $user  ログイン中のユーザー
     * @return bool 常に true
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * ユーザーが対象の書籍を更新できるか判定する。
     *
     * 書籍の作成者本人のみ許可する。編集フォームの表示（edit）にも同じ判定を使う。
     *
     * @param  User  $user  ログイン中のユーザー
     * @param  Book  $book  更新対象の書籍
     * @return bool 作成者本人なら true
     */
    public function update(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    /**
     * ユーザーが対象の書籍を削除できるか判定する。
     *
     * 書籍の作成者本人のみ許可する。
     *
     * @param  User  $user  ログイン中のユーザー
     * @param  Book  $book  削除対象の書籍
     * @return bool 作成者本人なら true
     */
    public function delete(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }
}

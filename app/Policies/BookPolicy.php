<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
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

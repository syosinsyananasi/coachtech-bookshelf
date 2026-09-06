<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Review extends Model
{
    use HasFactory;

    /**
     * 複数代入を許可する属性。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'rating',
        'comment',
        'user_id',
        'book_id',
    ];

    /**
     * このレビューを作成したユーザー。
     *
     * reviews.user_id を外部キーとする多対1。
     *
     * @return BelongsTo<User, Review>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * このレビューが紐づく書籍。
     *
     * reviews.book_id を外部キーとする多対1。
     *
     * @return BelongsTo<Book, Review>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * このレビューに「いいね」したユーザー。
     *
     * 中間テーブル review_likes を介した多対多。
     *
     * @return BelongsToMany<User>
     */
    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'review_likes');
    }
}

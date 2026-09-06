<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    /**
     * 複数代入を許可する属性。
     *
     * user_id は含めない。作成者はリレーション経由（User::books()->create()）で
     * 設定し、リクエスト値からの差し込みを防ぐ。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'author',
        'isbn',
        'published_date',
        'description',
        'image_url',
    ];

    /**
     * この書籍に紐づくジャンル。
     *
     * 中間テーブル book_genre を介した多対多。
     *
     * @return BelongsToMany<Genre>
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    /**
     * この書籍を登録したユーザー。
     *
     * books.user_id を外部キーとする多対1。BookPolicy の所有者判定に使用する。
     *
     * @return BelongsTo<User, Book>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この書籍に紐づくレビュー。
     *
     * 1対多の関係。
     *
     * @return HasMany<Review>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}

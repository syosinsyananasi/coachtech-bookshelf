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
     * 属性のキャスト。
     *
     * published_date は Carbon に変換する。★応用の Blade が format('Y-m-d') を呼ぶため。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published_date' => 'date',
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

    /**
     * この書籍を対象にした読書計画（★応用）。
     *
     * 1対多の関係。書籍削除時は外部キーの cascade で計画も削除される。
     *
     * @return HasMany<ReadingPlan>
     */
    public function readingPlans(): HasMany
    {
        return $this->hasMany(ReadingPlan::class);
    }
}

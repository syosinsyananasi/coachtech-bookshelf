<?php

namespace App\Models;

use App\Enums\ReadingPlanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 読書計画（★応用 DR09）。
 *
 * ユーザーが書籍ごとに「いつまでに読むか」を計画するレコード。
 * 状態は ReadingPlanStatus（進行中 / 完了 / 期限切れ）で管理し、日次バッチが期限切れへ遷移させる。
 */
class ReadingPlan extends Model
{
    use HasFactory;

    /**
     * 複数代入を許可する属性。
     *
     * user_id は含めない。計画者はリレーション経由（User::readingPlans()->create()）で設定する。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'book_id',
        'status',
        'target_date',
        'completed_at',
    ];

    /**
     * 属性のキャスト。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ReadingPlanStatus::class,
        'target_date' => 'date',
        'completed_at' => 'datetime',
    ];

    /**
     * この計画を立てたユーザー。
     *
     * @return BelongsTo<User, ReadingPlan>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この計画の対象書籍。
     *
     * @return BelongsTo<Book, ReadingPlan>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}

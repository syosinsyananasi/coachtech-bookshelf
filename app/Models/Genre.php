<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    use HasFactory;

    /**
     * 複数代入を許可する属性。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * このジャンルに紐づく書籍。
     *
     * 中間テーブル book_genre を介した多対多。
     *
     * @return BelongsToMany<Book>
     */
    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class);
    }
}

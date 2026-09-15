<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * レビューを JSON に整形する Resource。
 *
 * 書籍詳細（AP02）の reviews 配列の要素として使用し、投稿者名・評価・コメント・投稿日時を含める。
 */
class ReviewResource extends JsonResource
{
    /**
     * レビューを配列に変換する。
     *
     * 投稿者名は user リレーションが読み込まれている場合のみ含める。
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

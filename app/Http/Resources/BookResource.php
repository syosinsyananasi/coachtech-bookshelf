<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 書籍を JSON に整形する Resource。
 *
 * 一覧（AP01）と詳細（AP02）で共用する。ジャンル・レビューはリレーションが読み込まれている場合のみ含め、
 * 平均評価・レビュー件数は withAvg / withCount（または loadAvg / loadCount）で付与された値を使う。
 */
class BookResource extends JsonResource
{
    /**
     * 書籍を配列に変換する。
     *
     * average_rating はレビューが無い書籍では null、ある場合は小数第 2 位に丸める。
     * reviews は詳細でのみ読み込むため、一覧のレスポンスには含まれない。
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'published_date' => $this->published_date,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            'average_rating' => $this->reviews_avg_rating === null ? null : round((float) $this->reviews_avg_rating, 2),
            'reviews_count' => $this->reviews_count,
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

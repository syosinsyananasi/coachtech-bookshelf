<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ジャンルを JSON に整形する Resource。
 *
 * 書籍の genres 配列の要素として使用する。
 */
class GenreResource extends JsonResource
{
    /**
     * ジャンルを配列に変換する。
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}

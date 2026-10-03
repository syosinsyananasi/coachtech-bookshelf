<?php

namespace Database\Factories;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReadingPlan>
 */
class ReadingPlanFactory extends Factory
{
    /**
     * 読書計画の初期値を定義する。
     *
     * 既定は「期日が 7 日後の進行中」。計画者・書籍は未指定なら新規作成して紐付ける。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => now()->addDays(7)->toDateString(),
            'completed_at' => null,
        ];
    }

    /**
     * 完了済み（5 日前に読了）の状態にする。
     */
    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now()->subDays(5),
        ]);
    }

    /**
     * 期限切れ（期日が 3 日前）の状態にする。
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => ReadingPlanStatus::Expired,
            'target_date' => now()->subDays(3)->toDateString(),
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReadingPlanSeeder extends Seeder
{
    /**
     * 読書計画の初期データを 6 件投入する（★応用）。
     *
     * 採点者がいつ実行しても同じ挙動になるよう、target_date は Carbon::today() 起点で動的に決める。
     * 主要シナリオ（3 日前・当日・期限切れ + 3 日後・対象外・完了済み）は山田太郎に集約し、
     * 鈴木花子の 1 件は他ユーザーの計画として認可（403）の確認に使う。
     * 同一ユーザーの進行中の重複制御に抵触しないよう、計画ごとに異なる書籍を割り当てる。
     */
    public function run(): void
    {
        $today = Carbon::today();
        $yamada = User::firstWhere('email', 'yamada@example.com');
        $suzuki = User::firstWhere('email', 'suzuki@example.com');
        $books = Book::orderBy('id')->take(6)->get();

        // 各行の並びは [計画者, 期日 target_date, 状態 status, 読了日時 completed_at（未読了は null）]
        $plans = collect([
            // 1. 3 日前リマインダー対象
            [$yamada, $today->copy()->addDays(3), ReadingPlanStatus::InProgress, null],
            // 2. 当日リマインダー対象
            [$yamada, $today->copy(), ReadingPlanStatus::InProgress, null],
            // 3. バッチで期限切れ化 + 3 日後再エンゲージメント対象（二重シナリオ）
            [$yamada, $today->copy()->subDays(3), ReadingPlanStatus::InProgress, null],
            // 4. リマインダー対象外
            [$yamada, $today->copy()->addDays(7), ReadingPlanStatus::InProgress, null],
            // 5. 完了済み（5 日前に読了）
            [$yamada, $today->copy()->subDays(10), ReadingPlanStatus::Completed, $today->copy()->subDays(5)],
            // 6. 他ユーザー（山田太郎でログイン中に /reading-plans/6/edit を直打ちすると 403）
            [$suzuki, $today->copy()->addDays(5), ReadingPlanStatus::InProgress, null],
        ]);

        // 書籍は ID 順の先頭 6 冊を、配列の添字 $index（0 始まり）の順に 1 冊ずつ割り当てる（$books も 0 始まり）
        $plans->each(function (array $plan, int $index) use ($books) {
            [$user, $targetDate, $status, $completedAt] = $plan;

            $user->readingPlans()->create([
                'book_id' => $books[$index]->id,
                'status' => $status,
                'target_date' => $targetDate,
                'completed_at' => $completedAt,
            ]);
        });
    }
}

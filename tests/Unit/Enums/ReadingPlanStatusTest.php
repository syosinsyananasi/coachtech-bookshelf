<?php

namespace Tests\Unit\Enums;

use App\Enums\ReadingPlanStatus;
use PHPUnit\Framework\TestCase;

/**
 * ReadingPlanStatus Enum（★応用）。DB に保存する値、画面表示用のラベルとバッジ色を検証する。
 */
class ReadingPlanStatusTest extends TestCase
{
    /**
     * 3 つの状態が PM 回答（Q-022）どおりの文字列値を持つこと。
     */
    public function test_values_match_pm_answer(): void
    {
        $this->assertSame('in_progress', ReadingPlanStatus::InProgress->value);
        $this->assertSame('completed', ReadingPlanStatus::Completed->value);
        $this->assertSame('expired', ReadingPlanStatus::Expired->value);
    }

    /**
     * label() が日本語の表示名を返すこと。
     */
    public function test_label_returns_japanese_name(): void
    {
        $this->assertSame('進行中', ReadingPlanStatus::InProgress->label());
        $this->assertSame('完了', ReadingPlanStatus::Completed->label());
        $this->assertSame('期限切れ', ReadingPlanStatus::Expired->label());
    }

    /**
     * badgeClass() が状態ごとに異なる Tailwind のクラスを返すこと（青・緑・赤）。
     */
    public function test_badge_class_differs_per_status(): void
    {
        $this->assertStringContainsString('blue', ReadingPlanStatus::InProgress->badgeClass());
        $this->assertStringContainsString('green', ReadingPlanStatus::Completed->badgeClass());
        $this->assertStringContainsString('red', ReadingPlanStatus::Expired->badgeClass());
    }

    /**
     * 文字列から Enum に変換でき、未知の値は tryFrom で null になること（一覧の絞り込みで使う）。
     */
    public function test_from_and_try_from(): void
    {
        $this->assertSame(ReadingPlanStatus::Completed, ReadingPlanStatus::from('completed'));
        $this->assertNull(ReadingPlanStatus::tryFrom('unknown'));
    }
}

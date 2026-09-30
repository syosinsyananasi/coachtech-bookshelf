<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 読書計画テーブル（★応用 DR09）。
     *
     * user_id × book_id の複合 UNIQUE は張らない。完了済みの書籍は再計画できるため（シーディング要件）。
     * 進行中の重複はフォームリクエスト側で制御する。
     */
    public function up(): void
    {
        Schema::create('reading_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // 計画者
            $table->foreignId('book_id')->constrained()->onDelete('cascade'); // 対象書籍
            $table->string('status', 20)->default('in_progress'); // PHP Enum ReadingPlanStatus の値をそのまま格納
            $table->date('target_date'); // 読了期日
            $table->timestamp('completed_at')->nullable(); // 読了時に now() を格納
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_plans');
    }
};

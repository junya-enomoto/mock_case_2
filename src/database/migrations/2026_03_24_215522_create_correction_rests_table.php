<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correction_rests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('correction_id')->constrained('correction')->onDelete('cascade'); // どの修正申請に対する休憩か
            $table->foreignId('original_rest_id')->nullable()->constrained('rests')->onDelete('cascade'); // 元の休憩ID (修正の場合)
            $table->time('start_time_new'); // 修正後の休憩開始時間
            $table->time('end_time_new')->nullable();   // 修正後の休憩終了時間
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correction_rests');
    }
};

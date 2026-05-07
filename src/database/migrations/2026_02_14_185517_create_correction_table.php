<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('correction', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade'); // どの勤怠に対する修正か
            $table->time('clock_in_new')->nullable();  // 修正後の出勤時間
            $table->time('clock_out_new')->nullable(); // 修正後の退勤時間
            $table->text('remarks'); // 備考
            $table->string('status')->default('pending'); // pending(承認待ち), approved(承認), rejected(却下)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correction');
    }
};

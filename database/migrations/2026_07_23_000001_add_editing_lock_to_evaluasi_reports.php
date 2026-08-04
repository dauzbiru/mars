<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluasi_reports', function (Blueprint $table) {
            $table->foreignId('editing_user_id')->nullable()->after('tanggal')->constrained('users')->nullOnDelete();
            $table->dateTime('editing_at')->nullable()->after('editing_user_id');
            $table->json('editing_snapshot')->nullable()->after('editing_at');
        });
    }

    public function down(): void
    {
        Schema::table('evaluasi_reports', function (Blueprint $table) {
            $table->dropForeign(['editing_user_id']);
            $table->dropColumn(['editing_user_id', 'editing_at', 'editing_snapshot']);
        });
    }
};

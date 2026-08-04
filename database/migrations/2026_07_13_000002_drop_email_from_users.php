<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'email')) {
                return;
            }

            // SQLite tidak mengizinkan DROP COLUMN selama index yang merujuk
            // kolom tersebut masih ada. Index harus di-drop terlebih dahulu.
            $table->dropUnique('users_email_unique');
            $table->dropColumn(['email', 'email_verified_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->after('username');
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['pgs', 'gerais'] as $table) {
            $rows = DB::table($table)
                ->where('no_telepon', 'like', '62%')
                ->get(['id', 'no_telepon']);

            foreach ($rows as $row) {
                $digits = preg_replace('/\D/', '', (string) $row->no_telepon);
                if (str_starts_with($digits, '62')) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['no_telepon' => '0' . substr($digits, 2)]);
                }
            }
        }
    }

    public function down(): void
    {
        foreach (['pgs', 'gerais'] as $table) {
            $rows = DB::table($table)
                ->where('no_telepon', 'like', '0%')
                ->get(['id', 'no_telepon']);

            foreach ($rows as $row) {
                $digits = preg_replace('/\D/', '', (string) $row->no_telepon);
                if (str_starts_with($digits, '0') && str_starts_with(substr($digits, 1), '8')) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['no_telepon' => '62' . substr($digits, 1)]);
                }
            }
        }
    }
};

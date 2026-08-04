<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSqliteToMysql extends Command
{
    protected $signature = 'sqlite:import {--chunk=500 : Jumlah baris per batch}';

    protected $description = 'Impor seluruh data dari database/database.sqlite ke koneksi MySQL aktif';

    private array $skipTables = [
        'migrations',
        'sessions',
        'password_reset_tokens',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    public function handle(): int
    {
        if (DB::getDefaultConnection() === 'sqlite') {
            $this->error('Default koneksi masih sqlite. Ubah DB_CONNECTION=mysql di .env dulu.');
            return self::FAILURE;
        }

        $source = DB::connection('sqlite_read');
        $target = DB::connection('mysql');

        if (!file_exists($source->getDatabaseName())) {
            $this->error('File database/database.sqlite tidak ditemukan.');
            return self::FAILURE;
        }

        $tables = collect($source->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))
            ->pluck('name')
            ->reject(fn ($t) => in_array($t, $this->skipTables, true))
            ->values();

        if ($tables->isEmpty()) {
            $this->error('Tidak ada tabel untuk diimpor.');
            return self::FAILURE;
        }

        $jsonCols = collect($target->select(
            "SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND DATA_TYPE = 'json'"
        ))->groupBy('TABLE_NAME')->map->pluck('COLUMN_NAME')->all();

        $chunk = max(1, (int) $this->option('chunk'));

        $target->statement('SET FOREIGN_KEY_CHECKS=0');
        $total = 0;

        foreach ($tables as $table) {
            if (!$target->getSchemaBuilder()->hasTable($table)) {
                $this->warn("Tabel {$table} tidak ada di MySQL, dilewati.");
                continue;
            }

            $count = 0;
            try {
                $target->table($table)->delete();

                $source->table($table)->orderBy('rowid')->chunk($chunk, function ($rows) use ($target, $table, $jsonCols, &$count) {
                    $data = $rows->map(function ($row) use ($table, $jsonCols) {
                        $item = (array) $row;
                        foreach ($jsonCols[$table] ?? [] as $col) {
                            if (!array_key_exists($col, $item)) {
                                continue;
                            }
                            $value = $item[$col];
                            if ($value === null) {
                                continue;
                            }
                            if (is_string($value) && $value !== '' && json_decode($value) === null) {
                                $item[$col] = json_encode($value);
                            }
                        }
                        return $item;
                    })->toArray();

                    if (!empty($data)) {
                        $target->table($table)->insert($data);
                    }
                    $count += count($data);
                });

                $total += $count;
                $this->info(sprintf('%-28s %d baris', $table, $count));
            } catch (\Throwable $e) {
                $this->error("Gagal impor tabel {$table}: " . $e->getMessage());
            }
        }

        $target->statement('SET FOREIGN_KEY_CHECKS=1');

        $this->newLine();
        $this->info("Import selesai. Total {$total} baris.");
        return self::SUCCESS;
    }
}

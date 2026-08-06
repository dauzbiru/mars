<?php

namespace App\Models\Concerns;

use App\Models\Result;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

trait Reportable
{
    const GRADE_A_THRESHOLD = 990;
    const GRADE_B_THRESHOLD = 975;
    const GRADE_C_THRESHOLD = 925;
    const GRADE_D_THRESHOLD = 900;

    public static function gradeFromScore(float $score): string
    {
        $bulat = round($score);
        if ($bulat >= self::GRADE_A_THRESHOLD) return 'A';
        if ($bulat >= self::GRADE_B_THRESHOLD) return 'B';
        if ($bulat >= self::GRADE_C_THRESHOLD) return 'C';
        if ($bulat >= self::GRADE_D_THRESHOLD) return 'D';
        return 'E';
    }

    protected static function bootReportable(): void
    {
        static::deleting(function ($report) {
            $ttdPaths = array_filter([$report->ttd_petugas, $report->ttd_pimpinan]);
            if ($ttdPaths) {
                Storage::disk('public')->delete(array_values($ttdPaths));
            }

            foreach ($report->tampilanGeraiBlocks as $block) {
                $block->delete();
            }
        });
    }

    public function results()
    {
        return $this->morphMany(Result::class, 'reportable');
    }

    public function tampilanGeraiBlocks()
    {
        return $this->morphMany(\App\Models\TampilanGeraiBlock::class, 'reportable')->orderBy('sort_order')->orderBy('id');
    }

    public function gerai()
    {
        return $this->belongsTo(\App\Models\Gerai::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function editingUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'editing_user_id');
    }

    public function isLocked(): bool
    {
        if (!$this->editing_user_id) {
            return false;
        }
        if ($this->editing_user_id === Auth::id()) {
            return false;
        }
        return true;
    }

    public function lockForEditing(array $snapshot): void
    {
        $this->update([
            'editing_user_id' => Auth::id(),
            'editing_at' => now(),
            'editing_snapshot' => $snapshot,
        ]);
    }

    public function unlockEditing(): void
    {
        $this->update([
            'editing_user_id' => null,
            'editing_at' => null,
            'editing_snapshot' => null,
        ]);
    }
}

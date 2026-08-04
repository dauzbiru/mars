<?php

namespace App\Models;

use App\Models\Concerns\Reportable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PraMonitoringReport extends Model
{
    use Reportable;

    protected $table = 'pra_monitoring_reports';
    protected $fillable = [
        'gerai_id', 'user_id', 'location', 'nilai', 'grade',
        'checkin_at', 'submit_at', 'is_pairing',
        'editing_user_id', 'editing_at', 'editing_snapshot',
        'major', 'minor', 'peringatan_awal',
        'ttd_petugas', 'ttd_pimpinan',
        'penjelasan_isi_3',
        'pengawas', 'rata_rata_aj', 'mesin_ozon', 'note',
        'kondisi_cat', 'kondisi_awning', 'kondisi_vinyl', 'kondisi_stiker_kaca',
    ];
    protected $casts = [
        'checkin_at' => 'datetime',
        'submit_at' => 'datetime',
        'editing_at' => 'datetime',
        'editing_snapshot' => 'array',
        'penjelasan_isi_3' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('no_pairing', function (Builder $query) {
            $query->where('is_pairing', false);
        });
    }

    public static function findWithPairing($id)
    {
        return static::withoutGlobalScope('no_pairing')->find($id);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringFinding extends Model
{
    protected $fillable = [
        'reportable_type',
        'reportable_id',
        'monitoring_report_id',
        'major',
        'minor',
        'peringatan_awal',
        'pengawas',
        'rata_rata_aj',
        'tds',
        'mesin_ozon',
        'note',
        'kondisi_cat',
        'kondisi_awning',
        'kondisi_vinyl',
        'kondisi_stiker_kaca',
        'ttd_petugas',
        'ttd_pimpinan',
        'penjelasan_isi',
        'penjelasan_isi_3',
    ];

    protected $hidden = [
        'reportable_type',
        'reportable_id',
    ];

    protected $casts = [
        'penjelasan_isi' => 'array',
        'penjelasan_isi_3' => 'array',
    ];

    public function reportable()
    {
        return $this->morphTo();
    }
}

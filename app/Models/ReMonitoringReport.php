<?php

namespace App\Models;

use App\Models\Concerns\Reportable;
use Illuminate\Database\Eloquent\Model;

class ReMonitoringReport extends Model
{
    use Reportable;

    protected $table = 're_monitoring_reports';
    protected $fillable = [
        'gerai_id', 'user_id', 'location', 'nilai', 'grade',
        'checkin_at', 'submit_at',
        'editing_user_id', 'editing_at', 'editing_snapshot',
        'major', 'minor', 'peringatan_awal',
        'ttd_petugas', 'ttd_pimpinan',
        'penjelasan_isi', 'penjelasan_isi_3',
        'pengawas', 'rata_rata_aj', 'tds', 'mesin_ozon', 'note',
        'kondisi_cat', 'kondisi_awning', 'kondisi_vinyl', 'kondisi_stiker_kaca',
    ];
    protected $casts = [
        'checkin_at' => 'datetime',
        'submit_at' => 'datetime',
        'editing_at' => 'datetime',
        'editing_snapshot' => 'array',
        'penjelasan_isi' => 'array',
        'penjelasan_isi_3' => 'array',
    ];
}

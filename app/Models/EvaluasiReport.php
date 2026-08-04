<?php

namespace App\Models;

use App\Models\Concerns\Reportable;
use Illuminate\Database\Eloquent\Model;

class EvaluasiReport extends Model
{
    use Reportable;

    protected $table = 'evaluasi_reports';
    protected $fillable = ['gerai_id', 'user_id', 'tanggal', 'catatan', 'keterangan', 'editing_user_id', 'editing_at', 'editing_snapshot'];
    protected $casts = [
        'tanggal' => 'date',
        'editing_at' => 'datetime',
        'editing_snapshot' => 'array',
    ];
}

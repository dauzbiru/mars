<?php

namespace App\Models;

use App\Models\Concerns\Reportable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PraMonitoringReport extends Model
{
    use Reportable;

    protected $table = 'pra_monitoring_reports';
    protected $fillable = ['gerai_id', 'user_id', 'location', 'nilai', 'grade', 'checkin_at', 'submit_at', 'is_pairing'];
    protected $casts = [
        'checkin_at' => 'datetime',
        'submit_at' => 'datetime',
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

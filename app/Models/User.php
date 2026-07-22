<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'username', 'password', 'role'];

    public function monitoringReports(): HasMany
    {
        return $this->hasMany(MonitoringReport::class);
    }

    public function praMonitoringReports(): HasMany
    {
        return $this->hasMany(PraMonitoringReport::class);
    }

    public function reMonitoringReports(): HasMany
    {
        return $this->hasMany(ReMonitoringReport::class);
    }

    public function evaluasiReports(): HasMany
    {
        return $this->hasMany(EvaluasiReport::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}

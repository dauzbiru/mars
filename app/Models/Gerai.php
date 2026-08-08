<?php

namespace App\Models;

use App\Models\Concerns\HasPhoneNumber;
use Illuminate\Database\Eloquent\Model;

class Gerai extends Model
{
    use HasPhoneNumber;

    protected $fillable = ['kode_gerai', 'nama_gerai', 'franchisee', 'alamat', 'email', 'no_telepon', 'opening_at', 'nama_kota', 'area', 'is_active', 'closed_at'];

    protected function casts(): array
    {
        return [
            'opening_at' => 'date:Y-m-d',
            'closed_at' => 'date:Y-m-d',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

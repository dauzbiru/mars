<?php

namespace App\Models;

use App\Models\Concerns\HasPhoneNumber;
use Illuminate\Database\Eloquent\Model;

class HargaAirBaku extends Model
{
    use HasPhoneNumber;

    protected $table = 'harga_air_baku';

    protected $fillable = ['kota', 'nama_supplier', 'harga_air_baku', 'pemilik', 'no_telepon'];
}

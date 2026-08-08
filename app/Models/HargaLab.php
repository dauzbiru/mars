<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HargaLab extends Model
{
    protected $table = 'harga_lab';

    protected $fillable = ['kota', 'laboratorium', 'mikrobiologi', 'fisika_kimia', 'lengkap', 'catatan', 'alamat'];
}

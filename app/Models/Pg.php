<?php

namespace App\Models;

use App\Models\Concerns\HasPhoneNumber;
use Illuminate\Database\Eloquent\Model;

class Pg extends Model
{
    use HasPhoneNumber;

    protected $fillable = ['nama_pg', 'kota', 'no_telepon'];
}

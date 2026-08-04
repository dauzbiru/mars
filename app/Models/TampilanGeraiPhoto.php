<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TampilanGeraiPhoto extends Model
{
    protected $fillable = [
        'block_id', 'foto', 'sort_order',
    ];

    public function block()
    {
        return $this->belongsTo(TampilanGeraiBlock::class, 'block_id');
    }

    public function deleteFile(): void
    {
        if ($this->foto) {
            Storage::delete($this->foto);
        }
    }

    public function url(): string
    {
        return '/tampilan-gerai/foto/' . $this->id;
    }
}

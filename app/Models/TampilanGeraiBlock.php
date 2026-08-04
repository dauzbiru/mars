<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TampilanGeraiBlock extends Model
{
    protected $fillable = [
        'reportable_type', 'reportable_id', 'user_id',
        'keterangan', 'sort_order',
    ];

    public function reportable()
    {
        return $this->morphTo();
    }

    public function photos()
    {
        return $this->hasMany(TampilanGeraiPhoto::class, 'block_id')->orderBy('sort_order')->orderBy('id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deleteFiles(): void
    {
        foreach ($this->photos as $photo) {
            $photo->deleteFile();
        }
    }

    protected static function booted(): void
    {
        static::deleting(function (TampilanGeraiBlock $block) {
            $block->load('photos');
            $block->deleteFiles();
        });
    }
}

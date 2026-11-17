<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('kategori', timestamps: false)]
#[Fillable(['kode', 'nama', 'divisi_usulan_id', 'aktif'])]
class Kategori extends Model
{
    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function divisiUsulan(): BelongsTo
    {
        return $this->belongsTo(Divisi::class, 'divisi_usulan_id');
    }
}
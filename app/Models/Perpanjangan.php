<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('perpanjangan', timestamps: false)]
#[Fillable(['layanan_id', 'tambahan_hk', 'alasan', 'oleh'])]
class Perpanjangan extends Model
{
    protected function casts(): array
    {
        return ['tenggat_lama' => 'date', 'tenggat_baru' => 'date', 'pada' => 'datetime'];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'oleh');
    }
}
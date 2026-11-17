<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('riwayat_layanan', timestamps: false)]
class RiwayatLayanan extends Model
{
    protected function casts(): array
    {
        return ['pada' => 'datetime'];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'oleh');
    }
}
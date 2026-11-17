<?php
namespace App\Models;

use App\Enums\Peran;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Table('pengguna', timestamps: false)]
#[Fillable(['nama', 'username', 'email', 'password_hash', 'peran', 'divisi_id', 'aktif'])]
#[Hidden(['password_hash', 'remember_token'])]
class Pengguna extends Authenticatable
{
    protected $authPasswordName = 'password_hash';

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'peran' => Peran::class,
            'aktif' => 'boolean',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Divisi::class);
    }
}

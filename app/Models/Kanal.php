<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('kanal', timestamps: false)]
#[Fillable(['kode', 'nama', 'aktif'])]
class Kanal extends Model
{
    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }
}
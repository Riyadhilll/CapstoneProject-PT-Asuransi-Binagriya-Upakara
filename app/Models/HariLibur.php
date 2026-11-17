<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('hari_libur', key: 'tanggal', keyType: 'string', incrementing: false, timestamps: false)]
#[Fillable(['tanggal', 'nama'])]
class HariLibur extends Model {}
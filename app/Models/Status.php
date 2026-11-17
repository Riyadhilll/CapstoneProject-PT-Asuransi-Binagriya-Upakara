<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('status', key: 'kode', keyType: 'string', incrementing: false, timestamps: false)]
#[Fillable(['nama', 'urutan'])]
class Status extends Model {}
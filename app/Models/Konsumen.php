<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('konsumen', timestamps: false)]
#[Fillable(['nama', 'email', 'telepon'])]
class Konsumen extends Model {}
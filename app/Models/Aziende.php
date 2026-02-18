<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aziende extends Model
{
    protected $table = 'aziende';

    // Campi che possono essere riempiti in massa
    protected $fillable = [
        'nome',
    ];
}

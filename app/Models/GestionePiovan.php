<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GestionePiovan extends Model
{
    protected $table = 'enpoint_piovan';

    // Campi che possono essere riempiti in massa
    protected $fillable = [
        'endpoint',
        'chiamata_soap',
        'azienda'
    ];
}

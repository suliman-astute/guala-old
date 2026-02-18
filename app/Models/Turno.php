<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    protected $table = 'turni';

    protected $fillable = [
        'nome_turno',
        'inizio',
        'fine',
        'azienda',
        // altri campi se li aggiungi
    ];
}
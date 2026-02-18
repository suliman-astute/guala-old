<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GestioneTurnoPresse extends Model
{
    use HasFactory;

    // Nome della tabella
    protected $table = 'gestione_turni_presse';

    // Campi che possono essere riempiti in massa
    protected $fillable = [
        'id_capoturno',
        'id_turno',
        'id_operatori',
        'id_macchinari_associati',
        'data_turno',
    ];

    // Se data_turno deve essere trattata come data
    protected $casts = [
        'data_turno' => 'date',
    ];
}

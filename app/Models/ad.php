<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ad extends Model
{
    protected $table = 'table_gestione_ad';

    protected $fillable = [
        'dominio',
        'host',
        'base_dn',
        'porta',
        // altri campi se li aggiungi
    ];
}

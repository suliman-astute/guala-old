<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodiciOggetto extends Model
{
    protected $table = 'codici_oggetti';

    protected $fillable = [
        'codici',
        'oggetto',
    ];
}

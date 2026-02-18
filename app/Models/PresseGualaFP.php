<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresseGualaFP extends Model
{
    protected $table = 'presse_guala_fp';
    protected $primaryKey = 'id';

    protected $fillable = [
        'GUAPosition',
        'id_mes',
        'id_piovan',
        'ingressi_usati',
        'GUAMachineCenterType',
        'azienda',
    ];
}   
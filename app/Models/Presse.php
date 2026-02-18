<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presse extends Model
{
    protected $table = 'machine_center';
    protected $primaryKey = 'id';
    public $timestamps = false; // metti true solo se hai created_at/updated_at

    protected $fillable = [
        'GUAPosition',
        'id_mes',
        'id_piovan',
        'azienda',
    ];
}

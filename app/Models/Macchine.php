<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Macchine extends Model
{
    protected $table = 'machine_center'; // nome tabella reale
    protected $primaryKey = 'id';
    public $timestamps = false; // metti true solo se hai created_at/updated_at

    protected $fillable = [
        'GUAPosition',
        'name',
        'no',
        'id_piovan',
        'azienda',
    ];
}

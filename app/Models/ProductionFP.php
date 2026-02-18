<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionFP extends Model
{
    protected $table = 'stampaggio_fp';
    protected $primaryKey = 'id';

    protected $fillable = [
        'mesOrderNo',
        'mesStatus',
        'startingdatetime',
        'itemNo',
        'itemDescription',
        'machineSatmp',
        'machinePress',
        'machinePressDesc',
        'guaCustomerNO',
        'guaCustomName',
        'guaCustomerOrder',
        'quantity',
        'relSequence',
        'quantita_prodotta',
        'GUAPosition',
        'commento',
    ];
}

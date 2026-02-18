<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtInfos extends Model
{

    //Ho creato una classe dummy per test manterrei la struttura di quella di origine.
    //Non ho messo il log per evitare di impestare il DB di log.

    protected $fillable = [
        "val1",
        "val2",
        "val3",
        "stampe",
        "seq",
        "code",
        "n_order",
        "n_order_erp",
        "qta_ric",
        "qta_prod"
    ];
}

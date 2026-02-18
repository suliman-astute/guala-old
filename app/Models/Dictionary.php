<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dictionary extends Model
{
    protected $table = 'dictionary_table'; // Nome della tabella!
    protected $guarded = [];
    Public $timestamps = false;
}
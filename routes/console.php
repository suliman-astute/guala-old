<?php

use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;


//Ho creato un cron che gira ogni minuto. Va attivato sul server e temporizzato
//Loggo inizio e fine procedura per poter valutare i tempi di esecuzione
Schedule::call(function () {
    Log::debug("Inizio Import: ".date("d-m-Y H:i:s"));
    ImportController::import();
    Log::debug("Fine Import: ".date("d-m-Y H:i:s"));
})->everyMinute();

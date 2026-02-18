<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateDictionaryOnLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event)
    {
        if (empty($event->user->admin) || !$event->user->admin) {
            return; // NON è admin: non fa nulla
        }

        $entries = config('dizionario');
        foreach ($entries as $entry) {
             $tableName = $entry['table_name'];
            $column = $entry['column_name'];
            \Log::info('Inizio aggiornamento dizionario...');
            \App\Models\Dictionary::firstOrCreate([
                'table_name' => $tableName,
                'column_name' => $column
            ]);
            \Log::info("Salvata colonna $column nella tabella $tableName");

        }
    }
}

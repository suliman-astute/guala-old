<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DictionaryController extends Controller
{
    // Visualizza tabella blade
    public function showTraduzioni()
    {
        return view('traduzioni.table');
    }

    // Dati JSON per la grid
    public function json()
    {
        /* $traduzioni = DB::table('dictionary_table')->get();
        return response()->json(['data' => $traduzioni]); */
        $rows = DB::table('dictionary_table')
        ->orderBy('table_name')
        ->orderBy('id')
        ->get();

        // Raggruppa per table_name
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->table_name][] = $row;
        }

        // Prepara risultato "piatto" per la grid
        $result = [];
        foreach ($grouped as $tableName => $items) {
            // Righe normali
            foreach ($items as $item) {
                $item->is_group = false;
                $result[] = $item;
            }
        }

        return response()->json(['data' => array_values($result)]);
    }

    public function aggiornaDizionario()
    {
        $tables = \DB::select('SHOW TABLES');
        $tableKey = 'Tables_in_' . \DB::getDatabaseName();

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;

            // Escludi tabelle che NON ti interessano
            if (in_array($tableName, ['migrations', 'dictionary'])) continue;

            $columns = \Schema::getColumnListing($tableName);

            foreach ($columns as $column) {
                \App\Models\Dictionary::firstOrCreate([
                    'table_name' => $tableName,
                    'column_name' => $column
                ]);
            }
        }

        return 'Dizionario aggiornato!';
    }

    // Form creazione (modale)
    public function create($id = 0)
    {
        // Se arriva un id, cerca la traduzione, altrimenti oggetto vuoto per il form
        if ($id) {
            $data['traduzione'] = DB::table('dictionary_table')->find($id);
        } else {
            // Crea un oggetto vuoto con le stesse proprietà per evitare errori nel form
            $data['traduzione'] = (object)[
                'id' => null,
                'IT' => '',
                'EN'=> ''
            ];
        }

        return view('traduzioni.form', $data);
    }

    // Salva nuovo record
    public function store(Request $request)
    {
        DB::table('dictionary_table')->insert([
            'IT' => $request->input('IT'),
            'EN' => $request->input('EN'),
        ]);
        return response()->json(['success' => true]);
    }

    // Form modifica (modale)
    public function edit($id)
    {
        $traduzione = DB::table('dictionary_table')->find($id);
        return view('traduzioni.form', compact('traduzione'));
    }

    // Aggiorna record
    public function update(Request $request, $id)
    {
        DB::table('dictionary_table')->where('id', $id)->update([
            'IT' => $request->input('IT'),
            'EN' => $request->input('EN'),
        ]);
        return response()->json(['success' => true]);
    }

    // Cancella record
    public function delete($id)
    {
        DB::table('dictionary_table')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    public function getLabel($tableName, $columnName)
    {
        // prendo la lingua dell’utente loggato (default IT se non loggato o non settata)
        $lang = auth()->check() ? auth()->user()->lang : 'IT';

        // cerco la traduzione
        $label = DB::table('dictionary_table')
            ->where('table_name', $tableName)
            ->where('column_name', $columnName)
            ->value($lang);

        // se non trovata ritorno almeno il nome colonna
        return $label ?: $columnName;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TurniController extends Controller
{
    public function index()
    {
        $data = [];
        $data['page'] = 'Turni';
        return view('turni.table', $data);
    }

    public function json()
    {
        $turni = Turno::all();
        $data = [];

        foreach ($turni as $turno) {
            $nomeAzienda = DB::table('aziende')
                ->where('id', $turno->azienda)   // qui metti l'id che ti serve
                ->value('nome');
            $data[] = [
                'id' => $turno->id,
                'nome' => $turno->nome_turno,
                'inizio' => $turno->inizio,
                'fine' => $turno->fine,
                'azienda' => $nomeAzienda,
                // aggiungi altri campi se servono
            ];
        }

        return response()->json(['data' => $data]);
    }

    public function create($id = 0)
    {
        if ($id) {
            $data['turno'] = Turno::find($id);
        } else {
            $data['turno'] = new Turno();
        }
        $aziende = DB::table('aziende')
            ->pluck('nome', 'id')
            ->toArray();
        $data['aziende'] = $aziende;

        return view('turni.form', $data);
    }

    public function store(Request $request)
    {
        $turno = $request->id ? Turno::find($request->id) : new Turno;

        $rules = [
            'nome_turno' => 'required|string|max:255',
            'inizio' => 'required',
            'fine' => 'required',
            'azienda' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all()]);
        }

        $turno->nome_turno = $request->nome_turno;
        $turno->inizio = $request->inizio;
        $turno->fine = $request->fine;
        $turno->azienda = $request->azienda;
        // aggiungi altri campi se servono

        $turno->save();

        return response()->json(['success' => true, 'turno' => $turno]);
    }

    public function destroy(Request $request)
    {
        $turno = Turno::find($request->id_to_del);
        $turno->delete();
    }
}

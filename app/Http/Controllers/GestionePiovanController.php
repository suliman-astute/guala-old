<?php

namespace App\Http\Controllers;

use App\Models\GestionePiovan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;  
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GestionePiovanController extends Controller
{
    public function index()
    {
        return view('gestione_piovan.table', ['page' => 'Piovan']);
    }
    public function json()
    {
        //Json per tabella
        $gestione = GestionePiovan::all();
        $data = [];
        
        foreach ($gestione as $gs) {
            $nomeAzienda = DB::table('aziende')
                ->where('id', $gs->azienda)   // qui metti l'id che ti serve
                ->value('nome');
            $data[] = [
                'id' => $gs->id,
                'endpoint' => $gs->endpoint,
                'chiamata_soap' => $gs->chiamata_soap,
                'azienda' => $nomeAzienda,
                // aggiungi altri campi se servono
            ];
        }

        return response()->json(['data' => $data]);
    }

    public function create($id = 0)
    {
        //Form di creazione o modifica
        //Faccio un controllo sulla presenza dell'id decido se precompilare il form o meno
        if ($id) {
            $data["gestione_piovan"] = GestionePiovan::find($id);
        } else {
            $data["gestione_piovan"] = new GestionePiovan();
        }
        $aziende = DB::table('aziende')
            ->pluck('nome', 'id')
            ->toArray();
        $data['aziende'] = $aziende;

        return view('gestione_piovan.form', $data);
    }

    public function store(Request $request)
    {
        //Inserimento o modifica
        //Faccio un controllo sulla presenza dell'id decido quali dei due casi
        if ($request->id) {
            $GestionePiovan = GestionePiovan::find($request->id);
        } else {
            $GestionePiovan = new GestionePiovan;
        }

        $validator_list = [
            'endpoint' => 'required',
            'chiamata_soap' => 'required',
            'azienda' => 'required',
        ];

        $validator = Validator::make($request->all(), $validator_list);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all()]);
        } else {
           
            $row = $request->id ? GestionePiovan::find($request->id) : new GestionePiovan(); // <-- plurale
            $row->endpoint  = $request->endpoint;   // <-- campi giusti
            $row->chiamata_soap = $request->chiamata_soap;  // <-- campi giusti
            $row->azienda = $request->azienda;  // <-- campi giusti
            $row->save();

            return response()->json(['success' => true, 'row' => $row]);
            
        }

    }

    public function destroy(Request $request)
    {
        //Eliminazione
        $GestionePiovan = GestionePiovan::find($request->id_to_del);
        $GestionePiovan->delete();
    }
}

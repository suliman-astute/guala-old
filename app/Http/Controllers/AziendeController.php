<?php

namespace App\Http\Controllers;

use App\Models\Aziende;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;  
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AziendeController extends Controller
{
    public function index()
    {
        return view('aziende.table', ['page' => 'Aziende']);
    }
    public function json()
    {
        //Json per tabella
        $data = Aziende::all();

        return response()->json([
            'data' => $data
        ]);
    }

    public function create($id = 0)
    {
        //Form di creazione o modifica
        //Faccio un controllo sulla presenza dell'id decido se precompilare il form o meno
        if ($id) {
            $data["aziende"] = Aziende::find($id);
        } else {
            $data["aziende"] = new Aziende();
        }
        return view('aziende.form', $data);
    }

    public function store(Request $request)
    {
        //Inserimento o modifica
        //Faccio un controllo sulla presenza dell'id decido quali dei due casi
        if ($request->id) {
            $Aziende = Aziende::find($request->id);
        } else {
            $Aziende = new Aziende;
        }

        $validator_list = [
            'nome' => 'required',
        ];

        $validator = Validator::make($request->all(), $validator_list);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all()]);
        } else {
           
            $row = $request->id ? Aziende::find($request->id) : new Aziende(); // <-- plurale
            $row->nome  = $request->nome;
            $row->save();

            return response()->json(['success' => true, 'row' => $row]);
            
        }

    }

    public function destroy(Request $request)
    {
        //Eliminazione
        $Aziende = Aziende::find($request->id_to_del);
        $Aziende->delete();
    }
}


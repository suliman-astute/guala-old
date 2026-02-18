<?php

namespace App\Http\Controllers;

use App\Models\PresseGualaFP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;  
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PresseGualaFPController extends Controller
{
    public function index()
    {
        return view('presse_guala_fp.table', ['page' => 'Presse FP']);
    }
    public function json()
    {
        //Json per tabella
        $pgfp = PresseGualaFP::all();
        $data = [];
        foreach ($pgfp as $pg) {
            $nomeAzienda = DB::table('aziende')
                ->where('id', $pg->azienda)   // qui metti l'id che ti serve
                ->value('nome');
            $data[] = [
                'id' => $pg->id,
                'GUAPosition' => $pg->GUAPosition,
                'id_mes' => $pg->id_mes,
                'id_piovan' => $pg->id_piovan,
                'ingressi_usati' => $pg->ingressi_usati,
                'GUAMachineCenterType' => $pg->GUAMachineCenterType,
                'azienda' => $nomeAzienda,
                // aggiungi altri campi se servono
            ];
        }

        return response()->json([
            'data' => $data
        ]);
    }

    public function create($id = 0)
    {
        //Form di creazione o modifica
        //Faccio un controllo sulla presenza dell'id decido se precompilare il form o meno
       
        $presse_guala_fp = $id ? PresseGualaFP::find($id) : new PresseGualaFP();
        $aziende = DB::table('aziende')
                ->pluck('nome', 'id')
                ->toArray();

        return view('presse_guala_fp.form', compact('presse_guala_fp', 'aziende'));
    }

    public function store(Request $request)
    {
        //Inserimento o modifica
        //Faccio un controllo sulla presenza dell'id decido quali dei due casi
        if ($request->id) {
            $pgfp = PresseGualaFP::find($request->id);
        } else {
            $pgfp = new PresseGualaFP;
        }

        $validator_list = [
            'nome' => 'required',
            'GUAPosition' => 'required',
            'id_mes' => 'required',
            'id_piovan' => 'required',
            'ingressi_usati' => 'required',
            'GUAMachineCenterType' => 'required',
            'azienda' => 'required',
        ];

        $validator = Validator::make($request->all(), $validator_list);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all()]);
        } else {
      
            $row = $request->id ? PresseGualaFP::find($request->id) : new PresseGualaFP(); // <-- plurale
            $row->GUAPosition  = $request->GUAPosition;
            $row->id_mes  = $request->id_mes;
            $row->id_piovan  = $request->id_piovan;
            $row->ingressi_usati  = $request->ingressi_usati;
            $row->GUAMachineCenterType  = $request->GUAMachineCenterType;
            $row->azienda  = $request->azienda;
            $row->save();

            return response()->json(['success' => true, 'row' => $row]);
            
        }

    }

    public function destroy(Request $request)
    {
        //Eliminazione
        $pgfp = PresseGualaFP::find($request->id_to_del);
        $Aziende->delete();
    }
}


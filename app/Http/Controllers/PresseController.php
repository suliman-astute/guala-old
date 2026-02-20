<?php

namespace App\Http\Controllers;

use App\Models\Presse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PresseController extends Controller
{
    public function index()
    {
        $data = [];
        $data['page'] = 'Presse';
        return view('presse.table', $data);
    }

    public function json()
    {
        $presse = Presse::query()
            ->leftJoin('tabella_appoggio_macchine as tam', 'tam.no', '=', 'machine_center.no')
            ->where('machine_center.GUAMachineCenterType', '=', 'Pressing')
            ->select([
                'machine_center.id',
                'machine_center.GUAPosition',
                'machine_center.name',
                'machine_center.no',
                'machine_center.GUA_schedule',
                // prendi da tabella di appoggio; se vuoi fallback usa COALESCE(...)
                DB::raw('tam.id_mes as id_mes'),
                DB::raw('tam.ingressi_usati as ingressi_usati'),
                DB::raw('tam.id_piovan as id_piovan'),
                DB::raw('tam.azienda   as azienda'),
            ])
            ->get();

        $data = [];
        foreach ($presse as $pressa) {
            $nomeAzienda = DB::table('aziende')
                ->where('id', $pressa->azienda)   // qui metti l'id che ti serve
                ->value('nome');

            $data[] = [
                'id' => $pressa->id,
                'GUAPosition' => $pressa->GUAPosition,
                'name' => $pressa->name,
                'no' => $pressa->no,
                'id_mes' => $pressa->id_mes,
                'ingressi_usati' => $pressa->ingressi_usati,
                'id_piovan' => $pressa->id_piovan,
                'GUA_schedule' => $pressa->GUA_schedule,
                'azienda' => $nomeAzienda,
                // aggiungi altri campi se servono
            ];
        }

        return response()->json(['data' => $data]);
    }

public function create($id = 0)
{
    $aziende = DB::table('aziende')->pluck('nome', 'id')->toArray();

    if ($id) {
        $presse = DB::table('machine_center as mc')
            ->leftJoin('tabella_appoggio_macchine as tam', 'tam.no', '=', 'mc.no')
            ->where('mc.id', $id)
            ->select(
                'mc.*',
                'tam.id_mes',
                'tam.ingressi_usati',
                'tam.id_piovan',
                'tam.azienda'
            )
            ->first();
    } else {
        // Creiamo un oggetto "vuoto" con le proprietà necessarie per evitare errori in Blade
        $presse = (object) [
            'id' => null,
            'GUAPosition' => '',
            'id_mes' => '',
            'id_piovan' => '',
            'ingressi_usati' => '',
            'azienda' => ''
        ];
    }

    return view('presse.form', compact('presse', 'aziende'));
}

    public function store(Request $request)
    {
        $presse = $request->id ? Presse::find($request->id) : new Presse;

        $rules = [
            'GUAPosition' => 'string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all()]);
        }

   
        DB::transaction(function () use ($request, $presse) {
            // salva su machine_center (metti SOLO le colonne che esistono lì)
            $presse->GUAPosition = $request->input('GUAPosition');
            //$presse->id_mes          = $request->input('id_mes');


            $presse->save();

            // sincronizza tabella_appoggio_macchine (match per 'no')
            DB::table('tabella_appoggio_macchine')->updateOrInsert(
                ['no' => $presse->no],
                ['id_mes' => $request->input('id_mes'),
                'ingressi_usati' => $request->input('ingressi_usati'),
                'id_piovan' => $request->input('id_piovan'),
                'azienda'   => $request->input('azienda')]
            );
        });

        
        // --- risposta diversa a seconda del tipo di richiesta ---

        // chiamata AJAX → JSON
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'presse'  => $presse,
            ]);
        }

        // submit “normale” → redirect a una pagina
        return redirect()
            ->route('presse.table')   // è il nome che hai dato alla index
            ->with('success', 'Pressa salvata correttamente.');
    }

    public function destroy(Request $request)
    {
        $presse = Presse::find($request->id_to_del);
        $presse->delete();
    }
}

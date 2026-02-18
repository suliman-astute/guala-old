<?php

namespace App\Http\Controllers;

use App\Models\GestioneTurnoPresse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;  
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GestioneTurnoPresseController extends Controller
{
     public function index()
    {
        return view('app.APP3.index', ['page' => 'Gestione Turni Presse']);
    }

    public function json(Request $request)
    {
        $user = Auth::user();
        
        // Data target: oggi (oppure ?data=YYYY-MM-DD da querystring, se vuoi)
        $targetDate = $request->filled('data')
            ? Carbon::parse($request->input('data'))->toDateString()
            : Carbon::today()->toDateString();

        $q = GestioneTurnoPresse::query()
            ->whereDate('data_turno', $targetDate);

        if ($user["admin"] !=1) {
            $q->where('id_capoturno', $user->id);
        }

        $gestione_turni = $q->get();

        $data = [];
        foreach ($gestione_turni as $gt) {
            $turno = DB::table('turni')->where('id', $gt->id_turno)->value('nome_turno');

            // Se ti servono queste label tienile; altrimenti puoi rimuovere tutta la parte sotto.
            $idsOp  = json_decode($gt->id_operatori, true) ?: [];
            $idsMac = json_decode($gt->id_macchinari_associati, true) ?: [];

            $mapOp = DB::table('users')
                ->whereIn('id', $idsOp)
                ->where('ruolo_personale', 'Operatore Stampaggio')
                ->where('stato', 'attivo')
                ->pluck('name', 'id');

            //capoturno
            $capoturno = DB::table('users')
                ->selectRaw("
                    TRIM(CONCAT_WS(' ',
                        NULLIF(name, ''),
                        NULLIF(cognome, '')
                    )) AS full_name
                ")
                ->where('id', $gt->id_capoturno)
                ->value('full_name');

            $operatori = collect($idsOp)->map(fn($id) => $mapOp[$id] ?? null)->filter()->implode(', ');

            $mapMac = DB::table('machine_center as mc')
                ->leftJoin('tabella_appoggio_macchine as tam', 'tam.no', '=', 'mc.no')
                ->where('mc.GUAMachineCenterType', 'Pressing')
                ->where('mc.Company', 'Guala Dispensing FP')
                ->whereIn('mc.id', $idsMac)
                ->select(
                    'mc.id',
                    DB::raw("CONCAT(tam.id_mes, ' - ', tam.id_piovan, ' ( ',mc.name, ' )') AS label")
                )
                ->orderBy('mc.name')
                ->pluck('label', 'mc.id');

            $macchinari_associati = collect($idsMac)
                ->map(fn($id) => $mapMac[$id] ?? null)
                ->filter()
                ->implode(', ');

            $data[] = [
                'id'                   => $gt->id,
                'id_capoturno'         => $gt->id_capoturno,
                'capo_turno'           => $capoturno,
                'id_turno'             => $gt->id_turno,
                'turno'                => $turno,
                'operatori'            => $operatori,
                'macchinari_associati' => $macchinari_associati,
                'data_turno'           => optional($gt->data_turno)->format('Y-m-d'),
            ];
        }

        return response()->json(['data' => $data]);
    }

    public function create($id = null)
    {
        $gestione_turni = $id ? GestioneTurnoPresse::findOrFail($id) : new GestioneTurnoPresse();

        $operatori_associabili = DB::table('users')
            ->where('ruolo_personale', 'Operatore Stampaggio')
            ->where('stato', 'attivo')
            ->orderBy('name')
            ->pluck('name', 'id');

        //recupero i macchinari    
        $macchinari_associabili = DB::table('machine_center as mc')
            ->leftJoin('tabella_appoggio_macchine as tam', 'tam.no', '=', 'mc.no')
            ->where('mc.GUAMachineCenterType', 'Pressing')
            ->where('mc.Company', 'Guala Dispensing FP')
            ->select(
                'mc.id',
                DB::raw("CONCAT(tam.id_mes, ' - ', tam.id_piovan , ' ( ',mc.name, ' )') AS label")
            )
             ->having('label', '!=', '')
            ->orderBy('mc.name')
            ->pluck('label', 'mc.id');

        // attributo solo per la view
        $gestione_turni->setAttribute('operatori_associabili', $operatori_associabili);
        $gestione_turni->setAttribute('macchinari_associabili', $macchinari_associabili);

        return view('gestione_turni_presse.form', compact('gestione_turni'));
    }

    public function store(Request $request)
    {
        // 1) Normalizza: accetta array | "3" | "3,4" | "[3,4]" | "" -> [3,4]
        $idsOp  = $this->toIdArray($request->input('id_operatori'));
        $idsMac = $this->toIdArray($request->input('id_macchinari_associati'));

        // Metti nel request i valori normalizzati, così la validate vede un array
        $request->merge([
            'id_operatori'            => $idsOp,
            'id_macchinari_associati' => $idsMac,
        ]);

        // 2) Valida (CAMBIA 'id' se la chiave in 'presse' ha altro nome!)
        $rules = [
            'id'                        => 'nullable|integer|exists:gestione_turni_presse,id',
            'id_capoturno'              => 'required|integer|exists:users,id',
            'id_turno'                  => 'required|integer|in:1,2,3',
            'data_turno'                => 'required|date_format:Y-m-d',

            'id_operatori'              => 'nullable|array',
            'id_operatori.*'            => 'integer|distinct|exists:users,id',

            'id_macchinari_associati'   => 'nullable|array',
            'id_macchinari_associati.*' => 'integer|distinct|exists:presse,id', // <-- QUI: verifica il nome colonna!
        ];

        /* $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all()], 422);
        } */

        // 3) Salva (JSON)
        $row = $request->id ? GestioneTurnoPresse::findOrFail($request->id) : new GestioneTurnoPresse();

        $row->id_capoturno            = (int)$request->id_capoturno;
        $row->id_turno                = (int)$request->id_turno;
        $row->data_turno              = $request->data_turno;

        $row->id_operatori            = $idsOp  ? json_encode($idsOp)  : null;
        $row->id_macchinari_associati = $idsMac ? json_encode($idsMac) : null;

        $row->save();

        return response()->json(['success' => true, 'gestione_turni_presse' => $row]);
    }

    public function destroy(Request $request)
    {
        // Tabella corretta: gestione_turni
        $request->validate([
            'id_to_del' => 'required|integer|exists:gestione_turni_presse,id',
        ]);

        $row = GestioneTurnoPresse::findOrFail($request->id_to_del);
        $row->delete();

        return response()->json(['success' => true]);
    }


    private function toIdArray($value): array
    {
        if (is_array($value)) {
            $arr = $value;
        } elseif (is_string($value) && Str::startsWith($value, '[')) {
            $arr = json_decode($value, true) ?? [];
        } elseif (is_string($value) && str_contains($value, ',')) {
            $arr = array_map('trim', explode(',', $value));
        } elseif (isset($value) && $value !== '') {
            $arr = [$value];
        } else {
            $arr = [];
        }

        return collect($arr)
            ->filter(fn($v) => $v !== '' && $v !== null)
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values()
            ->all();
    }
}

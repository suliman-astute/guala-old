<?php

namespace App\Http\Controllers;

use App\Models\GestioneTurni;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;  
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GestioneTurniController extends Controller
{
    public function index()
    {
        return view('app.APP2.index', ['page' => 'Gestione Turni Assemblaggio']);
    }

    public function json(Request $request)
    {
        $user = Auth::user();
        
        // data di riferimento: oggi
        $targetDate = Carbon::today()->toDateString();

        $query = GestioneTurni::query()
            ->whereDate('data_turno', $targetDate);

        if ($user["admin"] !=1) {
            $query->where('id_capoturno', $user->id);
        }

        $gestione_turni = $query->get();

        $data = [];

        foreach ($gestione_turni as $gt) {

            // recupero turno
            $turno = DB::table('turni')
                ->where('id', $gt->id_turno)
                ->value('nome_turno');

            // helper inline
            $toIdArray = function ($value) {
                if (is_array($value)) {
                    $arr = $value;
                } elseif (is_string($value) && str_starts_with($value, '[')) {
                    $arr = json_decode($value, true) ?? [];
                } elseif (is_string($value) && str_contains($value, ',')) {
                    $arr = array_map('trim', explode(',', $value));
                } elseif (isset($value) && $value !== '') {
                    $arr = [$value];
                } else {
                    $arr = [];
                }
                return collect($arr)->map(fn($v)=>(int)$v)->filter(fn($v)=>$v>0)->unique()->values()->all();
            };

            // operatori
            $idsOp = $toIdArray($gt->id_operatori);
            $operatori = '';
            if (!empty($idsOp)) {
                $mapOp = DB::table('users')
                    ->whereIn('id', $idsOp)
                    ->where('ruolo_personale', 'Operatore Assemblaggio')
                    ->where('stato', 'attivo')
                    ->pluck('name', 'id');

                $operatori = collect($idsOp)
                    ->map(fn($id) => $mapOp[$id] ?? null)
                    ->filter()
                    ->implode(', ');
            }

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

            // macchinari
            $idsMac = $toIdArray($gt->id_macchinari_associati);
            $macchinari_associati = '';
            if (!empty($idsMac)) {
                $mapMac = DB::table('machine_center')
                    ->where('GUAMachineCenterType', '=', 'Machine')
                    ->whereIn('id', $idsMac)
                    ->pluck('name', 'id');

                $macchinari_associati = collect($idsMac)
                    ->map(fn($id) => $mapMac[$id] ?? null)
                    ->filter()
                    ->implode(', ');
            }

            $data[] = [
                'id'                   => $gt->id,
                'id_capoturno'         => $gt->id_capoturno,
                'capo_turno'           => $capoturno,
                'id_turno'             => $gt->id_turno,
                'turno'                => $turno,
                'id_operatori'         => $gt->id_operatori,
                'operatori'            => $operatori,
                'macchinari_associati' => $macchinari_associati,
                'data_turno'           => optional($gt->data_turno)->format('Y-m-d'),
            ];
        }

        return response()->json(['data' => $data]);
    }

    public function create($id = null)
    {
        $gestione_turni = $id ? GestioneTurni::findOrFail($id) : new GestioneTurni();

        $operatori_associabili = DB::table('users')
            ->where('ruolo_personale', 'Operatore Assemblaggio')
            ->where('stato', 'attivo')
            ->orderBy('name')
            ->pluck('name', 'id');

        //recupero i macchinari    
        $macchinari_associabili = DB::table('machine_center')
            ->where('GUAMachineCenterType', '=', 'Machine')
            ->where('Company', '=', 'Guala Dispensing FP')
            ->orderBy('name')
            ->pluck('name', 'id');

        // attributo solo per la view
        $gestione_turni->setAttribute('operatori_associabili', $operatori_associabili);
        $gestione_turni->setAttribute('macchinari_associabili', $macchinari_associabili);

        return view('gestione_turni.form', compact('gestione_turni'));
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
            'id'                        => 'nullable|integer|exists:gestione_turni,id',
            'id_capoturno'              => 'required|integer|exists:users,id',
            'id_turno'                  => 'required|integer|in:1,2,3',
            'data_turno'                => 'required|date_format:Y-m-d',

            'id_operatori'              => 'nullable|array',
            'id_operatori.*'            => 'integer|distinct|exists:users,id',

            'id_macchinari_associati'   => 'nullable|array',
            'id_macchinari_associati.*' => 'integer|distinct|exists:presse,id', // <-- QUI: verifica il nome colonna!
        ];

        $validator = Validator::make($request->all(), $rules);
        //dd($request);

        /* if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all()], 422);
        } */

        // 3) Salva (JSON)
        $row = $request->id ? GestioneTurni::findOrFail($request->id) : new GestioneTurni();

        $row->id_capoturno            = (int)$request->id_capoturno;
        $row->id_turno                = (int)$request->id_turno;
        $row->data_turno              = $request->data_turno;

        $row->id_operatori            = $idsOp  ? json_encode($idsOp)  : null;
        $row->id_macchinari_associati = $idsMac ? json_encode($idsMac) : null;
        $row->save();

        return response()->json(['success' => true, 'gestione_turni' => $row]);
    }

    public function destroy(Request $request)
    {
        // Tabella corretta: gestione_turni
        $request->validate([
            'id_to_del' => 'required|integer|exists:gestione_turni,id',
        ]);

        $row = GestioneTurni::findOrFail($request->id_to_del);
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

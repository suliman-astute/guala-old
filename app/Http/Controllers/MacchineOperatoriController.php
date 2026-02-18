<?php

// app/Http/Controllers/MacchineOperatoreController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MacchineOperatoriController extends Controller
{
    public function index()
    {
        return view('app.macchine.index', [
            'page' => 'Associazione Macchine',
        ]);
    }

    public function json(Request $request)
    {
        $user = Auth::user();
        $onlyOwn = (($user['admin'] ?? 0) != 1); // non-admin -> filtro

        $date = $request->filled('data')
            ? Carbon::parse($request->input('data'))->toDateString()
            : Carbon::today()->toDateString();

        // 1) prendo i turni del giorno
        $turni = DB::table('gestione_turni as gt')
            ->whereDate('gt.data_turno', $date)
            ->when($onlyOwn, function ($q) use ($user) {
                $q->where(function ($qq) use ($user) {
                    $qq->where('gt.id_capoturno', $user->id)
                    ->orWhereRaw('JSON_CONTAINS(gt.id_operatori, CAST(? AS JSON))', [$user->id]);
                });
            })
            ->get(['gt.id as id_turno', 'gt.id_operatori', 'gt.id_macchinari_associati', 'gt.nota']);

        // ...

        // 2) mappa macchina -> { ops: [...], turno: id_turno }
        $machineMap   = []; 
        $allMachineIds = [];
        $allOpIds      = [];

        foreach ($turni as $t) {
            $ops = json_decode($t->id_operatori, true) ?: [];
            $ops = collect($ops)->map(fn($v)=>(int)$v)->filter()->unique()->values()->all();

            $macs = json_decode($t->id_macchinari_associati, true) ?: [];
            $macs = collect($macs)->map(fn($v)=>(int)$v)->filter()->unique()->values()->all();

            foreach ($macs as $mid) {
                if (!isset($machineMap[$mid])) {
                    $machineMap[$mid] = ['ops' => [], 'turno' => $t->id_turno, 'nota' => $t->nota];
                }
                $machineMap[$mid]['ops'] = array_values(array_unique(array_merge($machineMap[$mid]['ops'], $ops)));
                // se c'è già un turno associato alla macchina lo lasci invariato
            }

            $allMachineIds = array_values(array_unique(array_merge($allMachineIds, $macs)));
            $allOpIds      = array_values(array_unique(array_merge($allOpIds, $ops)));
        }



        // 3) mappa id_macchina -> label "id_mes - name"
        $mapMac = DB::table('machine_center as mc')
            ->leftJoin('tabella_appoggio_macchine as tam', 'tam.no', '=', 'mc.no')
            ->whereIn('mc.id', $allMachineIds)
            ->select(
                'mc.id',
                DB::raw("CONCAT(COALESCE(tam.id_mes, mc.no), ' - ', mc.name) AS label")
            )
            ->pluck('label', 'mc.id');

        // 4) mappa id_operatore -> nome completo (gestisce campi null)
        $mapOp = [];
        if (!empty($allOpIds)) {
            $mapOp = DB::table('users')
                ->whereIn('id', $allOpIds)
                ->select(
                    'id',
                    DB::raw("TRIM(CONCAT_WS(' ', NULLIF(name,''),  NULLIF(cognome,''))) AS full_name")
                )
                ->pluck('full_name', 'id');
        }

        // 5) output
        $data = [];
        foreach ($machineMap as $mid => $payload) {
            $operatori = collect($payload['ops'])
                ->map(fn($id) => $mapOp[$id] ?? null)
                ->filter()
                ->implode(', ');

            $data[] = [
                'id'    => $payload['turno'],         // id riga gestione_turni
                'id_macchina' => $mid,
                'macchina'    => $mapMac[$mid] ?? ("ID ".$mid),
                'operatori'   => $operatori,
                'nota'        => $payload['nota']
            ];
        }

        // ordino per etichetta macchina
        usort($data, fn($a,$b)=>strcmp($a['macchina'],$b['macchina']));

        return response()->json(['data' => $data], 200, [], JSON_UNESCAPED_UNICODE);
    }


    public function saveNota(Request $request)
    {
        $data = $request->validate([
            'id'   => 'required|integer',
            'nota' => 'nullable|string|max:2000',
        ]);

        DB::table('gestione_turni')                      // <— cambia se il nome è diverso
            ->where('id', $data['id'])
            ->update(['nota' => $data['nota'] ?? '', 'updated_at' => now()]);

        return response()->json(['ok' => true]);
    }
}


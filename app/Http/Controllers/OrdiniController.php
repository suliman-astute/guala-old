<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OrdiniController extends Controller
{
    public function index()
    {
        return view('app.ordini.index', [
            'page' => 'Ordini Lotti',
            'messaggio' => 'Benvenuto OPERATORE'
        ]);
    }

    /* public function json()
    {
        //$user_id = Auth::user()->id;
        $user_id = 16;
        //$user_id = Auth::id(); // o 16 per test

        // 1) righe del turno dell’utente
        $rows = DB::table('gestione_turni_presse as gtp')
            ->whereNotNull('gtp.id_operatori')
            ->whereJsonContains('gtp.id_operatori', (int) $user_id)
            ->orderByDesc('gtp.data_turno')
            ->get();

        // 2) estraggo tutti gli id macchina che servono
        $idsNeeded = $rows->pluck('id_macchinari_associati')
            ->map(fn($v) => json_decode($v, true) ?: [])
            ->flatten()->filter()->unique()->values()->all();

        // 3) info macchine: label "id_mes - name" + ordini SAP (nrordinesap)
        $macInfo = DB::table('machine_center as mc')
            ->leftJoin('tabella_appoggio_macchine as tam', 'tam.no', '=', 'mc.no')
            ->leftJoin('bisio_progetti_stain as bps', 'bps.nome', '=', 'tam.id_mes')
            ->whereIn('mc.id', $idsNeeded)
            ->select(
                'mc.id',
                'mc.name',
                DB::raw("COALESCE(tam.id_mes, mc.no) as id_mes"),
                'bps.nrordinesap'
            )
            ->get()
            ->groupBy('id')
            ->map(function ($g) {
                $first = $g->first();
                return [
                    'label'  => ($first->id_mes).' - '.$first->name,            // es. "C01 - FANUC ..."
                    'ordini' => $g->pluck('nrordinesap')->filter()->unique()->values()->all(), // ['SAP1','SAP2',...]
                ];
            });

        // 4) arricchisco ogni riga con macchinari + ordini
        $rows->transform(function ($r) use ($macInfo) {
            $ids = json_decode($r->id_macchinari_associati, true) ?: [];
            $r->macchinari = collect($ids)->map(function ($id) use ($macInfo) {
                $info = $macInfo[$id] ?? null;
                return $info ? (object)[
                    'id'     => $id,
                    'label'  => $info['label'],   // "id_mes - name"
                    'ordini' => $info['ordini'],  // array nrordinesap
                ] : null;
            })->filter()->values()->all();
            return $r;
        });

        // 5) risposta JSON
        return response()->json(['data' => $rows], 200, [], JSON_UNESCAPED_UNICODE);

    } */

    public function json(Request $request)
    {
        $user = Auth::user();
        $isAdmin = (($user['admin'] ?? 0) != 1);

        // Data target: oggi, oppure da querystring ?data=YYYY-MM-DD
        $targetDate = $request->filled('data')
            ? Carbon::parse($request->input('data'))->toDateString()
            : Carbon::today()->toDateString();

        // 1) Turni filtrati
        $rows = DB::table('gestione_turni_presse as gtp')
            ->whereDate('gtp.data_turno', $targetDate)
            ->when($isAdmin, function ($q) use ($user) {
                // solo turni dove l'utente è tra gli operatori
                $q->whereNotNull('gtp.id_operatori')
                ->whereJsonContains('gtp.id_operatori', (int)$user->id);
            })
            ->orderByDesc('gtp.data_turno')
            ->get();

        // 2) Macchine coinvolte SOLO nei turni filtrati
        $idsNeeded = $rows->pluck('id_macchinari_associati')
            ->map(fn($v) => json_decode($v, true) ?: [])
            ->flatten()->filter()->unique()->values()->all();

        if (empty($idsNeeded)) {
            return response()->json(['data' => []], 200, [], JSON_UNESCAPED_UNICODE);
        }

        // 3) Info macchine + ordini per le SOLE macchine filtrate (solo da presse_guala_fp (con la join alla tabella di appoggio per recuperare id_mes e id_piovan):

        $macInfoQ = DB::table('machine_center as mc')
            ->leftJoin('tabella_appoggio_macchine as tam', 'tam.no', '=', 'mc.no')  // id_mes / id_piovan
            ->leftJoin('bisio_progetti_stain as bps', 'bps.nome', '=', 'tam.id_mes') // ordini legati all'ID MES
            ->where('mc.GUAMachineCenterType', 'Pressing')
            ->whereIn('mc.id', $idsNeeded)
            ->select(
                'mc.id',
                'tam.id_mes',
                'tam.id_piovan',
                'mc.name',
                'bps.nrordinesap'
            );

        $macInfo = $macInfoQ->get()
            ->groupBy('id')
            ->map(function ($g) {
                $first = $g->first();
                return [
                    // etichetta SOLO da presse_guala_fp, come richiesto
                    'label'  => $first->id_mes.' - '.$first->id_piovan. ' ( '.$first->name.')',
                    'ordini' => $g->pluck('nrordinesap')->filter()->unique()->values()->all(),
                ];
            });

        // 4) arricchisci le righe con macchinari/ordini (solo quelle dei turni filtrati)
        $rows->transform(function ($r) use ($macInfo) {
            $ids = json_decode($r->id_macchinari_associati, true) ?: [];
            $r->macchinari = collect($ids)->map(function ($id) use ($macInfo) {
                $info = $macInfo[$id] ?? null;
                return $info ? (object)[
                    'id'     => $id,
                    'label'  => $info['label'],   // es. "C01 - Destination_75"
                    'ordini' => $info['ordini'],
                ] : null;
            })->filter()->values()->all();
            return $r;
        });

        return response()->json(['data' => $rows], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function dettaglio(Request $request)
    {
        $ordine = $request->query('ordine');
        abort_if(!$ordine, 400, 'Parametro "ordine" mancante');

        $righe = DB::table('ordini_lavoro_lotti as oll')
            ->leftJoin('table_piovan_import as tpi', 'tpi.lotto', '=', 'oll.Lotto')
            ->where('oll.Ordine', $ordine)
            ->select(
                'oll.Lotto as lotto',
                DB::raw("CONCAT(oll.ArticoloCodice, ' - ', oll.ArticoloDescrizione) as articolodescrizione"),
                'oll.QtaPrevOrdin as qta',
                'tpi.material as material',
            )
            ->orderBy('oll.Lotto')
            ->get();

        return response()->json(['ordine' => $ordine, 'righe' => $righe], 200, [], JSON_UNESCAPED_UNICODE);
    }
    
    public function piovan(Request $request)
    {
        $idMes = $request->query('id_mes');
        abort_if(!$idMes, 400, 'Parametro "id_mes" mancante');

        $presse_guala = DB::table('machine_center as mc')
            ->join('tabella_appoggio_macchine as tam', 'tam.no', '=', 'mc.no')
            ->where('mc.GUAMachineCenterType', 'Pressing')          // opzionale, se vuoi solo le presse
            ->where('mc.Company', 'Guala Dispensing FP')            // idem, se ti serve il filtro azienda
            ->where('tam.id_piovan', $idMes)
            ->select('tam.id_mes')
            ->orderByDesc('tam.updated_at') // se updated_at è su machine_center usa mc.updated_at
            ->first();

        $ordine_stain = DB::table('bisio_progetti_stain')
            ->where('nome', $presse_guala->id_mes) // usa ->id_mes
            ->select('nrordinesap')
            ->first();

        // 1) Prendo la lista dei componenti del BOM (solo i valori)
        //$components = DB::table('table_gua_items_in_producion')
        $components = DB::table('table_gua_items_in_producion_test')
            ->where('mesOrderNo', $ordine_stain->nrordinesap)
            ->pluck('componentNo')           // Collection di stringhe
            ->map(fn($v) => trim($v))        // pulizia
            ->filter()                       // rimuove null/vuoti
            ->unique()
            ->values();

        // 2) Se la lista è vuota, righe sarà vuota; altrimenti filtro con whereIn
        if ($components->isEmpty()) {
            $righe = collect();
        } else {
            //$righe = DB::table('table_piovan_import')
            $righe = DB::table('table_piovan_import_test')
                ->where('id_mes', $idMes)
                ->whereIn('material', $components)   // <-- qui cicli "componentNo" lato DB
                ->where(function ($q) {
                    $q->whereNotNull('material')->where('material', '!=', '');
                })
                ->select('material', 'lotto', 'updated_at')
                ->orderByDesc('updated_at')
                ->get();
        }

        return response()->json([
            'id_mes' => $idMes,
            'righe'  => $righe,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function listNote(Request $r)
    {
        $ordine = $r->query('ordine');
        if (!$ordine) return response()->json(['note' => []]);

        $rows = DB::table('ordine_note')
            ->where('ordine', $ordine)
            ->get(['lotto','nota']);

        $map = $rows->mapWithKeys(fn($x) => [$x->lotto => (string)$x->nota])->all();

        return response()->json(['note' => $map]);
    }

    public function saveNota(Request $r)
    {
        $data = $r->validate([
            'ordine' => 'required|string|max:100',
            'lotto'  => 'required|string|max:100',
            'nota'   => 'nullable|string|max:2000',
        ]);

        DB::table('ordine_note')->updateOrInsert(
            ['ordine' => $data['ordine'], 'lotto' => $data['lotto']],
            ['nota' => $data['nota'] ?? '', 'updated_at' => now(), 'created_at' => now()]
        );

        return response()->json(['ok' => true]);
    }

    public function salvaLotto(Request $request)
    {
        $idMes   = $request->input('id_mes');
        $material= $request->input('material');
        $lotto   = trim($request->input('lotto'));

        abort_if(!$idMes || !$material, 400, 'Parametri mancanti');

        DB::table('table_piovan_import')
            ->where('id_mes', $idMes)
            ->where('material', $material)
            ->update([
                'lotto'      => $lotto,
                'updated_at' => now(),
            ]);

        return response()->json(['success' => true, 'lotto' => $lotto]);
    }

}

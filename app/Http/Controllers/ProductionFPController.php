<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use App\Models\ProductionFP;
use App\Models\Macchine;
use Carbon\Carbon; 

class ProductionFPController extends Controller
{
    
    public function index(Request $request)
    {
        $q = ProductionFP::query();
            //->whereNotNull('mesOrderNo')->where('mesOrderNo','!=','')
            //->whereNotNull('itemNo')->where('itemNo','!=','');
       
        // filtro date
        $from = $request->query('from');
        $to   = $request->query('to');
        if ($from || $to) {
            $start = $from ? Carbon::parse($from)->startOfDay() : null;
            $end   = $to   ? Carbon::parse($to)->endOfDay()     : null;
            if ($start && $end)  $q->whereBetween('startingdatetime', [$start, $end]);
            elseif ($start)      $q->where('startingdatetime','>=',$start);
            else                 $q->where('startingdatetime','<=',$end);
        }

        // ordine
        $rows = $q->orderBy('startingdatetime')
                ->orderBy('operationNo')
                ->get();
        
        // ---- enrich macchine: no -> {GUAPosition, name}
        $presses = $rows->pluck('machinePress')->filter()->unique()->values();
        $macByNo = Macchine::query()
            ->whereIn('no', $presses)
            ->where('Company', 'Guala Dispensing FP')
            ->get(['no','GUAPosition','name'])
            ->keyBy('no');
        // Precarica mappa per GUAPosition realmente usate
        // Prioritize filtered Macchine data first - only use ProductionFP if machine exists in filtered Macchine
        $positions = $rows->map(function($r) use ($macByNo) {
                $mc = $macByNo->get($r->machinePress);
                if ($mc) {
                    // Machine exists in filtered Macchine - use its GUAPosition or ProductionFP as fallback
                    return trim((string)($mc->GUAPosition ?: $r->GUAPosition ?: ''));
                }
                // Machine not in filtered Macchine - return null (will be filtered out)
                return null;
            })
            ->filter()  // Removes null values (machines not in filtered Macchine)
            ->unique()
            ->values();

        $macByPos = Macchine::query()
            ->whereIn('GUAPosition', $positions)
            ->where('Company', 'Guala Dispensing FP')
            ->pluck('name','GUAPosition');   // ['I12' => '...', 'P12' => '...']

        // Filter rows to only include machines that exist in filtered Macchine (Company = 'Guala Dispensing FP')
        // This ensures only data from machines belonging to 'Guala Dispensing FP' is processed
        $rows = $rows->filter(function($row) use ($macByNo) {
            return $macByNo->has($row->machinePress);
        })->values();

        /* ===========================================================
        *  Prelevo TotaleQtaProdottaBuoni da table_guaprodrouting (batch)
        * =========================================================== */
        // --- QTA da table_guaprodrouting: chiave (prodOrderNo|operationNo)
        $keysQta = $rows->map(function($r){
            $po = (string)($r->prodOrderNo ?? $r->prodNo ?? '');
            $op = (string)($r->operationNo ?? '');
            return ($po && $op) ? ($po.'|'.$op) : null;
        })->filter()->unique()->values()->all();

        $qtaByKey = [];
        if (!empty($keysQta)) {
            $qtaRows = DB::table('table_guaprodrouting')
                ->select('prodOrderNo','operationNo','TotaleQtaProdottaBuoni')
                ->whereIn(DB::raw("CONCAT(prodOrderNo,'|',operationNo)"), $keysQta)
                ->get();

            foreach ($qtaRows as $rt) {
                $qtaByKey[$rt->prodOrderNo.'|'.$rt->operationNo] = (int)$rt->TotaleQtaProdottaBuoni;
            }
        }

        /* ===========================================================
        *  NUOVO: Prelevo StatoOperazione da bisio_progetti_stain (batch)
        *  chiave: nrordinesap (= mesOrderNo) + nome (= GUAPosition risolta)
        * =========================================================== */
        // Note: pairKeys calculation removed as it appears unused in the code

        $keysStato = $rows->map(function($r){
            $po = (string)($r->prodOrderNo ?? $r->prodNo ?? '');
            $no = trim((string)($r->machineSatmp ?? ''));   // ATT: machineSatmp
            return ($po && $no) ? (strtoupper($po).'|'.strtoupper($no)) : null;
        })->filter()->unique()->values()->all();

        $statoByNo = [];
        if (!empty($keysStato)) {
            $statoRows = DB::table('table_guaprodrouting')
                ->select('prodOrderNo','no','StatoOperazione')
                ->whereIn(DB::raw("CONCAT(UPPER(prodOrderNo),'|',UPPER(no))"), $keysStato)
                ->get();

            foreach ($statoRows as $rt) {
                $statoByNo[strtoupper($rt->prodOrderNo).'|'.strtoupper($rt->no)]
                    = $rt->StatoOperazione !== null ? (string)$rt->StatoOperazione : null;
            }
        }


        /**
         * PARTE COMMENTI
         * 
         */

        $ids =$rows
            ->map(function ($r) {
                $ms = trim((string)($r->machineSatmp ?? ''));
                $pn = trim((string)($r->prodOrderNo   ?? '')); // o prodNo se quello è il campo
                if ($ms === '' || $pn === '') return null;
                return $ms . '_' . $pn;
            })
            ->filter()               // rimuove null/empty
            ->unique()
            ->values()
            ->all();
        $itemNo =$rows
            ->map(function ($r) {
                $ms = trim((string)($r->itemNo ?? ''));
                return $ms;
            })
            ->filter()               // rimuove null/empty
            ->unique()
            ->values()
            ->all();

        // Commenti per No (prima tabella)
        $commentByItemNo = [];

        if (!empty($itemNo)) {
            $rowsCommenti = DB::table('table_commenti_guala_fp as tcg')
                ->whereIn('tcg.No', $itemNo)
                ->orderBy('tcg.No')              // opzionale, solo per avere ordine logico
                ->orderBy('tcg.LineNo', 'asc')   // dal LineNo più piccolo al più grande
                ->get(['tcg.No', 'tcg.Comment']);

            // [ No => "commento1\ncommento2\ncommento3" ]
            $commentByItemNo = $rowsCommenti
                ->groupBy('No')
                ->map(function ($group) {
                    // qui scegli tu il separatore: "\n", " - ", "<br>" ecc.
                    return $group->pluck('Comment')->implode("\n");
                })
                ->toArray();
        }

        // Commenti per id_riga (seconda tabella)
        $commentByIdRiga = [];
        if (!empty($ids)) {
            $commentByIdRiga = DB::table('commento_lavori_guala_fp as c')
                ->whereIn('c.id_riga', $ids)
                ->pluck('c.testo', 'c.id_riga')    // [ id_riga => testo ]
                ->toArray();
        }

        $commentByRow = [];   // opzionale, se vuoi un array [id_riga => commento]

        $rows = $rows->map(function ($r) use ($commentByItemNo, $commentByIdRiga, &$commentByRow) {
            // ricostruisco le chiavi come fatto prima
            $itemNo = trim((string)($r->itemNo ?? ''));

            $ms = trim((string)($r->machineSatmp ?? ''));
            $pn = trim((string)($r->prodOrderNo ?? ''));
            $idRiga = ($ms !== '' && $pn !== '') ? $ms . '_' . $pn : null;

            $comment = null;

            // 1) priorità: commento per itemNo (table_commenti_guala_fp)
            if ($itemNo !== '' && array_key_exists($itemNo, $commentByItemNo)) {
                $comment = $commentByItemNo[$itemNo];
            }
            // 2) fallback: commento per id_riga (commento_lavori_guala_fp)
            elseif ($idRiga !== null && array_key_exists($idRiga, $commentByIdRiga)) {
                $comment = $commentByIdRiga[$idRiga];
            }

            // lo appoggio sulla riga (così lo hai in output)
            $r->commento = $comment;

            // opzionale: se vuoi anche un array esterno [id_riga => commento]
            if ($idRiga !== null) {
                $commentByRow[$idRiga] = $comment;
            }

            return $r;
        });


        // mapper riga -> payload
        $map = function($row) use ($macByNo, $macByPos, $qtaByKey, $statoByNo, $commentByRow) {
            $pdfPath   = public_path("bolle_lavorazione_pdf/{$row->mesOrderNo}.pdf");
            $pdfExists = file_exists($pdfPath);



            // CHIAVI
            $prodOrderNo = (string)($row->prodOrderNo ?? $row->prodNo ?? '');
            $operationNo = (string)($row->operationNo ?? '');
            $noKey       = trim((string)($row->machineSatmp ?? '')); // per stato

            $mc  = $macByNo->get($row->machinePress);
            
            // Prioritize filtered Macchine data first - only use ProductionFP if machine exists in filtered Macchine
            if ($mc) {
                // Machine exists in filtered Macchine - use Macchine data first
                $pos = trim((string)($mc->GUAPosition ?: $row->GUAPosition ?: $row->machinePress ?: $row->machineSatmp ?: '-'));
            } else {
                // Machine not in filtered Macchine - use ProductionFP as fallback
                $pos = trim((string)($row->GUAPosition ?: $row->machinePress ?: $row->machineSatmp ?: '-'));
            }

            // PRIORITÀ a GUAPosition; se non trovata, ripiega su name da NO
            $nomeMacchina = $macByPos[$pos] ?? optional($mc)->name ?? '';
            $machinePressFull = $pos.'   -   '.$nomeMacchina;

            // lookup routing
            $prodOrderNo = (string)($row->prodOrderNo ?? $row->prodNo ?? '');
            $operationNo = (string)($row->operationNo ?? '');
            $routingKey  = $prodOrderNo.'|'.$operationNo;
            $totBuoni    = $qtaByKey[$routingKey] ?? 0;

            // << FIX >>: chiave normalizzata per StatoOperazione
            $ordJoin  = (string)($row->prodOrderNo ?: $row->mesOrderNo ?: '');
            $keyStato = strtoupper($ordJoin).'|'.strtoupper($pos);
            $statoOp  = $statoByNo[strtoupper($prodOrderNo).'|'.strtoupper($noKey)] ?? null;

            // Start Data view
            $startTs   = $row->startingdatetime ? \Carbon\Carbon::parse($row->startingdatetime)->timestamp : PHP_INT_MAX;
            $startDisp = $row->startingdatetime ? \Carbon\Carbon::parse($row->startingdatetime)->format('d/m/Y H:i:s') : null;

            $ms  = trim((string)($row->machineSatmp ?? ''));
            $pn  = trim((string)($row->prodOrderNo   ?? ''));
            $key = ($ms !== '' && $pn !== '') ? "{$ms}_{$pn}" : null;

            return [
                'id'                 => $row->id,
                'prodNo'             => $row->prodOrderNo ?? null,
                'mesOrderNo'         => $row->mesOrderNo,
                'mesStatus'          => $statoOp,
                'itemNo'             => $row->itemNo,
                'itemDescription'    => $row->itemDescription,
                'machineSatmp'       => $row->machineSatmp,
                'machinePress'       => $row->machinePress,
                'machinePressDesc'   => $row->machinePressDesc,
                'GUA_schedule'       => $row->GUA_schedule,
                'commento'           => $commentByRow[$key] ?? null,

                // arricchimenti - prioritize filtered Macchine data
                'GUAPosition'        => $mc ? ($mc->GUAPosition ?: $row->GUAPosition) : ($row->GUAPosition ?: null),
                'machineName'        => $nomeMacchina,
                'machinePressFull'   => $machinePressFull,

                // quantità
                'quantity'           => (int) $row->quantity,
                'quantita_prodotta'  => (int) $totBuoni,      // dai routing
                'quantita_rimanente' => max(0, (int) $row->quantity - (int) $totBuoni),

                'starting_at_sort'  => $startTs,     // <— SOLO per ordinare
                'startingdatetime'  => $startDisp,   // <— SOLO per mostrare
                'pdf_exists'         => $pdfExists,
                'is_group'           => false,
            ];
        };

        $data = array_map($map, $rows->all());

        // ordinamento per GUAPosition, poi startingdatetime
        usort($data, function($a, $b) {
            $pa = trim((string)($a['GUAPosition'] ?? ''));
            $pb = trim((string)($b['GUAPosition'] ?? ''));
            $cmp = strnatcasecmp($pa, $pb);
            if ($cmp !== 0) return $cmp;
            $sa = $a['starting_at_sort'] ?? PHP_INT_MAX;
            $sb = $b['starting_at_sort'] ?? PHP_INT_MAX;
            return $sa <=> $sb;
        });

        // split PH/F
        $schedule = strtoupper((string)$request->query('schedule',''));
        if ($schedule === 'PH' || $schedule === 'F') {
            $bucket = array_values(array_filter($data, fn($r) => ($r['GUA_schedule'] ?? '') === $schedule));
            return response()->json($bucket);
        }

        $pharma = array_values(array_filter($data, fn($r) => ($r['GUA_schedule'] ?? '') === 'PH'));
        $food   = array_values(array_filter($data, fn($r) => ($r['GUA_schedule'] ?? '') === 'F'));

        return response()->json(['pharma' => $pharma, 'food' => $food]);
    }

    public function stampa(Request $request)
    {
        // base query
        $q = ProductionFP::query()
            ->whereNotNull('mesOrderNo')->where('mesOrderNo','!=','')
            ->whereNotNull('itemNo')->where('itemNo','!=','');

        // filtro date
        $from = $request->query('from');
        $to   = $request->query('to');
        if ($from || $to) {
            $start = $from ? Carbon::parse($from)->startOfDay() : null;
            $end   = $to   ? Carbon::parse($to)->endOfDay()     : null;
            if ($start && $end)  $q->whereBetween('startingdatetime', [$start, $end]);
            elseif ($start)      $q->where('startingdatetime','>=',$start);
            else                 $q->where('startingdatetime','<=',$end);
        }

        // ordinamento
        $rows = $q->orderBy('startingdatetime')
                ->orderBy('operationNo')   // rimuovi se la colonna non c’è
                ->get();

        // ---- preload macchine: NO -> {no, GUAPosition, name}
        $presses = $rows->pluck('machinePress')->filter()->unique()->values();
        $macByNo = Macchine::query()
            ->whereIn('no', $presses)
            ->get(['no','GUAPosition','name'])
            ->keyBy('no'); // [no => {no, GUAPosition, name}]

        // ---- preload nomi per GUAPosition effettivamente usate
        $positions = $rows->map(function($r) use ($macByNo) {
                return trim((string)($r->GUAPosition ?: optional($macByNo->get($r->machinePress))->GUAPosition));
            })
            ->filter()->unique()->values();

        $macByPos = Macchine::query()
            ->whereIn('GUAPosition', $positions)
            ->pluck('name','GUAPosition');  // ['I12'=>'...', 'P12'=>'...']

        /* ===========================================================
        *  preload da table_guaprodrouting (prodOrderNo + operationNo)
        * =========================================================== */
        $keysQta = $rows->map(function($r){
            $po = (string)($r->prodOrderNo ?? $r->prodNo ?? '');
            $op = (string)($r->operationNo ?? '');
            return ($po && $op) ? ($po.'|'.$op) : null;
        })->filter()->unique()->values()->all();

        $qtaByKey = [];
        if (!empty($keysQta)) {
            $qtaRows = DB::table('table_guaprodrouting')
                ->select('prodOrderNo','operationNo','TotaleQtaProdottaBuoni')
                ->whereIn(DB::raw("CONCAT(prodOrderNo,'|',operationNo)"), $keysQta)
                ->get();

            foreach ($qtaRows as $rt) {
                $qtaByKey[$rt->prodOrderNo.'|'.$rt->operationNo] = (int)$rt->TotaleQtaProdottaBuoni;
            }
        }

        /* ===========================================================
        *  NUOVO: preload StatoOperazione da bisio_progetti_stain
        *  chiave: nrordinesap (usa prodOrderNo, fallback mesOrderNo) +
        *          nome (= GUAPosition)  -- entrambi UPPER
        * =========================================================== */
        $pairKeys = [];
        foreach ($rows as $r) {
            $mc  = $macByNo->get($r->machinePress);
            $pos = trim((string)($r->GUAPosition
                ?: optional($mc)->GUAPosition
                ?: $r->machinePress
                ?: $r->machineSatmp
                ?: '-'));

            $ord = (string)($r->prodOrderNo ?: $r->mesOrderNo ?: '');
            if ($ord !== '' && $pos !== '' && $pos !== '-') {
                $pairKeys[] = strtoupper($ord).'|'.strtoupper($pos);
            }
        }
        $pairKeys = array_values(array_unique($pairKeys));
        $keysStato = $rows->map(function($r){
            $po = (string)($r->prodOrderNo ?? $r->prodNo ?? '');
            $no = trim((string)($r->machineSatmp ?? ''));   // ATT: machineSatmp
            return ($po && $no) ? (strtoupper($po).'|'.strtoupper($no)) : null;
        })->filter()->unique()->values()->all();

        $statoByNo = [];
        if (!empty($keysStato)) {
            $statoRows = DB::table('table_guaprodrouting')
                ->select('prodOrderNo','no','StatoOperazione')
                ->whereIn(DB::raw("CONCAT(UPPER(prodOrderNo),'|',UPPER(no))"), $keysStato)
                ->get();

            foreach ($statoRows as $rt) {
                $statoByNo[strtoupper($rt->prodOrderNo).'|'.strtoupper($rt->no)]
                    = $rt->StatoOperazione !== null ? (string)$rt->StatoOperazione : null;
            }
        }

        /**
         * PARTE COMMENTI
         * 
         */

        $ids = $rows
            ->map(function ($r) {
                $ms = trim((string)($r->machineSatmp ?? ''));
                $pn = trim((string)($r->prodOrderNo   ?? '')); // o prodNo se quello è il campo
                if ($ms === '' || $pn === '') return null;
                return $ms . '_' . $pn;
            })
            ->filter()               // rimuove null/empty
            ->unique()
            ->values()
            ->all();

        $itemNo = $rows
            ->map(function ($r) {
                $ms = trim((string)($r->itemNo ?? ''));
                return $ms;
            })
            ->filter()               // rimuove null/empty
            ->unique()
            ->values()
            ->all();

        // Commenti per No (prima tabella, NAV) con concatenazione per LineNo
        $commentByItemNo = [];

        if (!empty($itemNo)) {
            $rowsCommenti = DB::table('table_commenti_guala_fp as tcg')
                ->whereIn('tcg.No', $itemNo)
                ->orderBy('tcg.No')              // solo per ordine logico
                ->orderBy('tcg.LineNo', 'asc')   // dal LineNo più piccolo al più grande
                ->get(['tcg.No', 'tcg.Comment']);

            // [ No => "commento1\ncommento2\ncommento3" ]
            $commentByItemNo = $rowsCommenti
                ->groupBy('No')
                ->map(function ($group) {
                    // separatore a scelta: "\n", " - ", "<br>"...
                    return $group->pluck('Comment')->implode("\n");
                })
                ->toArray();
        }

        // Commenti per id_riga (seconda tabella, manuali)
        $commentByIdRiga = [];
        if (!empty($ids)) {
            $commentByIdRiga = DB::table('commento_lavori_guala_fp as c')
                ->whereIn('c.id_riga', $ids)
                ->pluck('c.testo', 'c.id_riga')   // => [ id_riga => testo ]
                ->toArray();
        }

        $commentByRow = [];   // [id_riga => commento] usato nel mapper PDF

        $rows = $rows->map(function ($r) use ($commentByItemNo, $commentByIdRiga, &$commentByRow) {
            // ricostruisco le chiavi come fatto sopra
            $itemNo = trim((string)($r->itemNo ?? ''));

            $ms = trim((string)($r->machineSatmp ?? ''));
            $pn = trim((string)($r->prodOrderNo ?? ''));
            $idRiga = ($ms !== '' && $pn !== '') ? $ms . '_' . $pn : null;

            $comment = null;

            // 1) priorità: commento per itemNo (table_commenti_guala_fp)
            if ($itemNo !== '' && array_key_exists($itemNo, $commentByItemNo)) {
                $comment = $commentByItemNo[$itemNo];
            }
            // 2) fallback: commento per id_riga (commento_lavori_guala_fp)
            elseif ($idRiga !== null && array_key_exists($idRiga, $commentByIdRiga)) {
                $comment = $commentByIdRiga[$idRiga];
            }

            // lo appoggio sulla riga se ti dovesse servire in view
            $r->commento = $comment;

            // array esterno per il mapper
            if ($idRiga !== null) {
                $commentByRow[$idRiga] = $comment;
            }

            return $r;
        });

        // mapper riga -> payload
        $map = function($row) use ($macByNo, $macByPos, $qtaByKey, $statoByNo, $commentByRow) {
            $pdfPath   = public_path("bolle_lavorazione_pdf/{$row->mesOrderNo}.pdf");
            $pdfExists = file_exists($pdfPath);

            $mc  = $macByNo->get($row->machinePress); // può essere null

               // CHIAVI
            $prodOrderNo = (string)($row->prodOrderNo ?? $row->prodNo ?? '');
            $operationNo = (string)($row->operationNo ?? '');
            $noKey       = trim((string)($row->machineSatmp ?? '')); // per stato

            // posizione calcolata (GUAPosition > NO > stamp)
            $pos = trim((string)($row->GUAPosition
                ?: optional($mc)->GUAPosition
                ?: $row->machinePress
                ?: $row->machineSatmp
                ?: '-'));

            // PRIORITÀ al nome da GUAPosition, fallback su nome da NO, poi descrizione
            $nomeMacchina = $macByPos[$pos]
                ?? optional($mc)->name
                ?? ($row->machinePressDesc ?: '');

            $machinePressFull = $pos.'   -   '.$nomeMacchina;

            // lookup su table_guaprodrouting
            $prodOrderNo = (string)($row->prodOrderNo ?? $row->prodNo ?? '');
            $operationNo = (string)($row->operationNo ?? '');
            $routingKey  = $prodOrderNo.'|'.$operationNo;
            $totBuoni    = $qtaByKey[$routingKey] ?? 0;

            // lookup StatoOperazione: chiave normalizzata (ORD|POS)
            $ordJoin  = (string)($row->prodOrderNo ?: $row->mesOrderNo ?: '');
            $keyStato = strtoupper($ordJoin).'|'.strtoupper($pos);
            $statoOp  = $statoByNo[strtoupper($prodOrderNo).'|'.strtoupper($noKey)] ?? null;
            
            // Start Data view
            $startTs   = $row->startingdatetime ? \Carbon\Carbon::parse($row->startingdatetime)->timestamp : PHP_INT_MAX;
            $startDisp = $row->startingdatetime ? \Carbon\Carbon::parse($row->startingdatetime)->format('d/m/Y H:i:s') : null;

            $ms  = trim((string)($row->machineSatmp ?? ''));
            $pn  = trim((string)($row->prodOrderNo   ?? ''));
            $key = ($ms !== '' && $pn !== '') ? "{$ms}_{$pn}" : null;

            return [
                'id'                 => $row->id,
                'prodNo'             => $row->prodOrderNo ?? null,
                'mesOrderNo'         => $row->mesOrderNo,
                'mesStatus'          => $statoOp,                // << scrivo qui >>
                'itemNo'             => $row->itemNo,
                'itemDescription'    => $row->itemDescription,
                'machineSatmp'       => $row->machineSatmp,
                'machinePress'       => $row->machinePress,
                'machinePressDesc'   => $row->machinePressDesc,
                'GUA_schedule'       => $row->GUA_schedule,
                'commento'           => $commentByRow[$key] ?? null,

                // arricchimenti
                'GUAPosition'        => ($row->GUAPosition ?: optional($mc)->GUAPosition),
                'machineName'        => $nomeMacchina,
                'machinePressFull'   => $machinePressFull,
                'StatoOperazione'    => $statoOp,                // se vuoi anche campo dedicato
                'TotaleQtaProdottaBuoni' => $totBuoni,

                // quantità
                'quantity'           => (int) $row->quantity,
                'quantita_prodotta'  => (int) $totBuoni,
                'quantita_rimanente' => max(0, (int) $row->quantity - (int) $totBuoni),

                'starting_at_sort'  => $startTs,     // <— SOLO per ordinare
                'startingdatetime'  => $startDisp,   // <— SOLO per mostrare

                'pdf_exists'         => $pdfExists,
                'is_group'           => false,
            ];
        };

        // grouping per intestazioni macchina nel PDF
        $groupByMachine = function(array $data) {
            $by = [];
            foreach ($data as $r) {
                $key = $r['machinePressFull']; // es. "P34   -   ARBURG 820 H ..."
                if (!isset($by[$key])) $by[$key] = ['pos' => $r['GUAPosition'] ?? null, 'rows' => []];
                $by[$key]['rows'][] = $r;
            }
            $out = [];
            foreach ($by as $key => $pack) {
                $out[] = ['is_group' => true, 'GUAPosition' => $pack['pos'], 'machinePressFull' => $key];
                foreach ($pack['rows'] as $it) $out[] = $it;
            }
            return $out;
        };

        $all = array_map($map, $rows->all());

        // ordinamento per GUAPosition + startingdatetime
        usort($all, function($a, $b) {
            $pa = trim((string)($a['GUAPosition'] ?? ''));
            $pb = trim((string)($b['GUAPosition'] ?? ''));
            $cmp = strnatcasecmp($pa, $pb);
            if ($cmp !== 0) return $cmp;
            $sa = $a['starting_at_sort'] ?? PHP_INT_MAX;
            $sb = $b['starting_at_sort'] ?? PHP_INT_MAX;
            return $sa <=> $sb;
        });

        $schedule = strtoupper((string)$request->query('schedule',''));

        if ($schedule === 'PH' || $schedule === 'F') {
            $bucket = array_values(array_filter($all, fn($r) => ($r['GUA_schedule'] ?? '') === $schedule));
            $righe  = $groupByMachine($bucket);
            $title_add = $schedule === 'PH' ? 'Pharma' : 'Food';
            return view('app.Monitor_Fp.PDF_Stampaggio_Fp.index', [
                'title' => 'Monitor FP '.$title_add,
                'righe' => $righe,
            ]);
        }

        $ph = array_values(array_filter($all, fn($r) => ($r['GUA_schedule'] ?? '') === 'PH'));
        $fd = array_values(array_filter($all, fn($r) => ($r['GUA_schedule'] ?? '') === 'F'));

        return view('app.Monitor_Fp.PDF_Stampaggio_Fp.index', [
            'title'   => 'Monitor FP',
            'righePH' => $groupByMachine($ph),
            'righeF'  => $groupByMachine($fd),
        ]);
    }

    public function updateCommento(Request $request)
    {
        
        // 1) valida i parametri in arrivo dal JS
        $data = $request->validate([
            'machineSatmp' => 'string',
            'prodNo'       => 'string',   // = mesOrderNo
            'commento'     => 'nullable|string',
        ]);

        $idRiga   = (string) $data['machineSatmp'].'_'.$data['prodNo'];
        $commento = (string) ($data['commento'] ?? '');

        // se vuoi mantenere created_at solo in insert:
        $affected = DB::table('commento_lavori_guala_fp')
            ->where('id_riga', $idRiga)
            ->update([
                'testo'      => $commento,
                'updated_at' => now(),
            ]);

        if ($affected === 0) {
            DB::table('commento_lavori_guala_fp')->insert([
                'id_riga'    => $idRiga,
                'testo'      => $commento,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }
}

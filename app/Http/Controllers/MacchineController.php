<?php

namespace App\Http\Controllers;

use App\Models\Macchine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class MacchineController extends Controller
{
    public function index()
    {
        return view('macchine.table', ['page' => 'Macchine']);
    }

    public function json()
    {
        // Prendi tutte le righe
        $macchine = Macchine::all();

        $rows = Macchine::query()
            ->leftJoin('tabella_appoggio_macchine as tam', 'tam.no', '=', 'machine_center.no')
            ->where('machine_center.GUAMachineCenterType', '=', 'Machine')
            ->select([
                'machine_center.id',
                'machine_center.GUAPosition',
                'machine_center.name',
                'machine_center.no',
                // prendi da tabella di appoggio; se vuoi fallback usa COALESCE(...)
                DB::raw('tam.azienda   as azienda'),
            ])
            ->get();
        foreach ($rows as $row) {
            $row->azienda = DB::table('aziende')
                ->where('id', $row->azienda)
                ->value('nome');
        }
        return response()->json(['data' => $rows]);
    }

    public function create($id = 0)
    {
        $macchina = $id ? Macchine::find($id) : new Macchine();
        $aziende = DB::table('aziende')
                ->pluck('nome', 'id')
                ->toArray();

        return view('macchine.form', compact('macchina', 'aziende'));
    }

    public function store(Request $request)
    {
        $macchina = $request->id ? Macchine::find($request->id) : new Macchine();
        if ($request->id && !$macchina) {
            return response()->json(['error' => ['Macchina non trovata.']], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'GUAPosition' => 'nullable|string',
            'no'          => 'required|string|max:255',
            'id_piovan'   => 'nullable|string',
            'azienda'     => 'nullable|string|max:255', // <--
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all()], 422);
        }

        DB::transaction(function () use ($request, $macchina) {
            // salva su machine_center (metti SOLO le colonne che esistono lì)
            $macchina->name        = $request->input('name');
            $macchina->GUAPosition = $request->input('GUAPosition');
            $macchina->no          = $request->input('no');

            // Se queste colonne esistono in machine_center, scommenta:
            // $macchina->id_piovan   = $request->input('id_piovan');
            // $macchina->azienda     = $request->input('azienda');

            $macchina->save();

            // sincronizza tabella_appoggio_macchine (match per 'no')
            DB::table('tabella_appoggio_macchine')->updateOrInsert(
                ['no' => $macchina->no],
                ['azienda'   => $request->input('azienda')]
            );
        });

        return response()->json(['success' => true, 'macchina' => $macchina]);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'id_to_del' => 'required|integer|exists:machine_center,id',
        ]);

        $macchina = Macchine::find($request->id_to_del);
        $macchina->delete();

        return response()->json(['success' => true]);
    }

    

}

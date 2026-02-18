<?php

namespace App\Http\Controllers;

use App\Models\CodiciOggetto;              // <-- model plurale
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CodiciOggettiController extends Controller
{
    public function index()
    {
        return view('codici_oggetto.table', ['page' => 'Codici Oggetto']);
    }

    public function json()
    {
        $rows = CodiciOggetto::select('id','codici','oggetto')->get();

        return response()->json([
            'data' => $rows->map(fn($r) => [
                'id'      => $r->id,
                'codici'  => $r->codici,
                'oggetto' => $r->oggetto,
            ]),
        ]);
    }

    public function create($id = null)
    {
        $row = $id ? CodiciOggetto::find($id) : new CodiciOggetto();   // <-- plurale
        return view('codici_oggetto.form', ['codice_oggetto' => $row]); // <-- stessa cartella della table
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'codici'  => 'required|string|max:255',
            'oggetto' => 'required|string|max:255',
            'id'      => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all()], 422);
        }

        $row = $request->id ? CodiciOggetto::find($request->id) : new CodiciOggetto(); // <-- plurale
        $row->codici  = $request->codici;   // <-- campi giusti
        $row->oggetto = $request->oggetto;  // <-- campi giusti
        $row->save();

        return response()->json(['success' => true, 'row' => $row]);
    }

    public function destroy(Request $request)
    {
        $row = CodiciOggetto::findOrFail($request->id_to_del); // <-- plurale
        $row->delete();

        return response()->json(['success' => true]);
    }
}

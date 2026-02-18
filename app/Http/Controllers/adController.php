<?php

namespace App\Http\Controllers;

use App\Models\ad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class adController extends Controller
{
    public function index()
    {
        $data = [];
        $data['page'] = 'Gestione AD';
        return view('ad.table', $data);
    }

    public function json()
    {
        $ad = ad::all();
        $data = [];

        foreach ($ad as $a) {
            $data[] = [
                'id' => $a->id,
                'dominio' => $a->dominio,
                'host' => $a->host,
                'base_dn' => $a->base_dn,
                'porta' => $a->porta,
            ];
        }

        return response()->json(['data' => $data]);
    }

    public function create($id = 0)
    {
        if ($id) {
            $data['ad'] = ad::find($id);
        } else {
            $data['ad'] = new ad();
        }

        return view('ad.form', $data);
    }

    public function store(Request $request)
    {
        $ad = $request->id ? ad::find($request->id) : new ad;

        $rules = [
            'dominio' => 'required|string|max:255',
            'host'    => 'required|string|max:255',
            'base_dn' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    // controlla che ci sia almeno una virgola
                    if (strpos($value, ',') === false) {
                        $fail("Il campo Base DN deve contenere più valori separati da virgola.");
                    }
                },
            ],
            'porta'   => 'required|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->all()
            ]);
        }

        $ad->dominio = $request->dominio;
        $ad->host    = $request->host;
        $ad->base_dn = $request->base_dn;
        $ad->porta   = $request->porta;

        try {
            $ad->save();
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'error' => ['Il dominio è già presente nel database.']
                ]);
            }

            return response()->json([
                'error' => ['Errore durante il salvataggio.']
            ]);
        }

        return response()->json([
            'success' => true,
            'ad'      => $ad
        ]);
    }


    public function destroy(Request $request)
    {
        $ad = ad::find($request->id_to_del);
        $ad->delete();
    }
}

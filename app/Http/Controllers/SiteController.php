<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiteController extends Controller
{

    public function index()
    {
        //Pagina di arrivo
        $data = array();
        $data["page"] = "Sites";
        return view('sites.table', $data);
    }

    public function json()
    {
        //Json per tabella
        $data = Site::all();
        return response()->json([
            'data' => $data
        ]);
    }

    public function create($id = 0)
    {
        //Form di creazione o modifica
        //Faccio un controllo sulla presenza dell'id decido se precompilare il form o meno
        if ($id) {
            $data["site"] = Site::find($id);
        } else {
            $data["site"] = new Site();
        }
        return view('sites.form', $data);
    }

    public function store(Request $request)
    {
        //Inserimento o modifica
        //Faccio un controllo sulla presenza dell'id decido quali dei due casi
        if ($request->id) {
            $site = Site::find($request->id);
        } else {
            $site = new Site;
        }

        $validator_list = [
            'name' => 'required',
        ];

        $validator = Validator::make($request->all(), $validator_list);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all()]);
        } else {
            $site->fill($request->all());
            $site->save();
        }

    }

    public function destroy(Request $request)
    {
        //Eliminazione
        $site = Site::find($request->id_to_del);
        $site->delete();
    }
}

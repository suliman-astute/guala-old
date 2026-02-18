<?php

namespace App\Http\Controllers;

use App\Models\ActiveApp;
use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;


class ActiveAppController extends Controller
{

    public function index()
    {
        //Pagina di arrivo
        $data = array();
        $data["page"] = "Active Apps";
        return view('active_apps.table', $data);
    }

    public function json()
    {
        //Json per tabella
        $data = ActiveApp::with("site")->get();
        return response()->json([
            'data' => $data
        ]);
    }

    public function create($id = 0)
    {
        //Form di creazione o modifica
        //Faccio un controllo sulla presenza dell'id decido se precompilare il form o meno
        if ($id) {
            $data["active_app"] = ActiveApp::find($id);
        } else {
            $data["active_app"] = new ActiveApp();
        }
        $data["sites"] = Site::orderby("name")->get();
         $aziende = DB::table('aziende')
                ->pluck('nome', 'id')
                ->toArray();
        $data['aziende'] = $aziende;

        return view('active_apps.form', $data);
    }

    public function store(Request $request)
    {
        //Inserimento o modifica
        //Faccio un controllo sulla presenza dell'id decido quali dei due casi
        if ($request->id) {
            $active_app = ActiveApp::find($request->id);
        } else {
            $active_app = new ActiveApp;
        }

        $validator_list = [
            'name_it' => 'required',
            'name_en' => 'required',
            'code' => [
                'required',
                Rule::unique('active_apps')->ignore($active_app->id)->whereNull('deleted_at'),
            ],
        ];

        $validator = Validator::make($request->all(), $validator_list);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all()]);
        } else {
            $active_app->fill($request->all());
            if ($request->file('icon')) {
                if ($active_app->icon) {
                    Storage::delete($active_app->icon); //Cancello immagine precedente
                }
                $active_app->icon = $request->file('icon')->store('active-app/icon');
            }
            $active_app->save();
        }

    }

    public function destroy(Request $request)
    {
        //Eliminazione
        $active_app = ActiveApp::find($request->id_to_del);
        $active_app->delete();
    }

    public function image($id)
    {
        //Resituisco l'immagine
        $active_app = ActiveApp::find($id);
        $slug = Str::slug($active_app->name) . ".jpg";
        if($active_app->icon)
            return Storage::response($active_app->icon, $slug);
        else
            return response()->file(public_path("images/logo.jpg"));
    }


    public function app1()
    {
        return view('app.APP1.index');
    }

    public function app2()
    {
        return view('app.APP2.table');
    }

}

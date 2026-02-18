<?php

namespace App\Http\Controllers;

use App\Models\ActiveApp;
use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{

    public function index()
    {
        //Pagina di arrivo
        $data = array();
        $data["page"] = "Users";
        return view('users.table', $data);
    }

    public function json()
    {
        //Json per tabella
        $users = User::all();
        $data = array();

        foreach($users AS $user){

            $aziende = DB::table('aziende')
                
                ->where('id', $user->destinazione_utenti)
                ->value('nome');

            $item=array();
            $item["id"] = $user->id;
            $item["name"] = $user->name;
            $item["admin"] = $user->admin;
            $item["email"] = $user->email;
            $item["matricola"] = $user->matricola;
            $item["is_ad_user"] = $user->is_ad_user;
            $item["destinazione_utenti"] = $aziende;
            $item["ruolo_personale"] = $user->ruolo_personale;
            $item["stato"] = $user->stato;

            if(!$user->admin){

                $item["site"] = $user->site?->name;
                $item["lang"] = $user->lang;

            } else {

                $item["site"] = "";
                $item["lang"] = "";
            }

            $data[] = $item;
            $aziende = DB::table('aziende')
                ->pluck('nome', 'id')
                ->toArray();

            $item['aziende'] = $aziende;
        }
        

        return response()->json([
            'data' => $data
        ]);
    }

    public function create($id = 0)
    {
        //Form di creazione o modifica
        //Faccio un controllo sulla presenza dell'id decido se precompilare il form o meno
        if ($id) {
            $data["user"] = User::find($id);
        } else {
            $data["user"] = new User();
        }

        $data["sites"] = Site::orderby("name")->get();
        $data["langs"] = User::langs();
        $aziende = DB::table('aziende')
            ->pluck('nome', 'id')
            ->toArray();
        $data['aziende'] = $aziende;
        return view('users.form', $data);
    }

    public function store(Request $request)
    {
        // Se esiste ID, modifica; altrimenti nuovo
        $user = $request->id ? User::find($request->id) : new User;

        // Inizializza lista di regole
        $rules = [
            'name' => 'required',
            'tipo_dominio' => 'nullable|string|max:255',
        ];

        // LOGICA UTENTE AD O LOCALE
        if ($request->id) {
            // Modifica: password facoltativa
            $rules['password'] = 'confirmed|nullable|min:8';
        } else {
            if ($request->is_ad_user == 0) {
                // Utente locale
                $rules['password'] = 'required|confirmed|min:8';
                $rules['email'] = [
                    Rule::unique('users')->ignore($user->id)->whereNull('deleted_at'),
                ];
            } else {
                // Utente AD: email può essere generata, ma serve almeno un campo obbligatorio
                $rules['name'] = 'required';
                // Se vuoi forzare una mail AD (es. nome@azienda), gestiscilo qui
                //$request->merge(['email' => $request->name]);
            }
        }

        // Esegui validazione
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all()]);
        }

        // Fill dei dati, escludi i campi che NON vuoi scrivere forzatamente
        $user->name = $request->name;
        $user->email = $request->email ?? null;
        $user->is_ad_user = $request->is_ad_user ?? 0;
        $user->tipo_dominio = $request->tipo_dominio ?? null;
        $user->admin = $request->admin ?? 0;
        $user->site_id = $request->site_id ?? 0;
        $user->lang = $request->lang ?? 0;
        $user->admin = $request->admin ?? 0;
        $user->matricola = $request->matricola ?? null;
        $user->cognome = $request->cognome ?? null;
        $user->user_id = $request->user_id ?? null;
        $user->destinazione_utenti = $request->destinazione_utenti ?? '';
        $user->ruolo_personale = $request->ruolo_personale ?? '';
        $user->stato = $request->stato ?? '';

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json(['success' => true, 'user' => $user]);
    }


    public function destroy(Request $request)
    {
        //Eliminazione
        $user = User::find($request->id_to_del);
        $user->delete();
        DB::table('users')
            ->where('id', $request->id_to_del)
            ->delete();
        //DISASSOCIO APP COLLEGATE
        DB::table('active_app_user')
            ->where('user_id', $request->id_to_del)
            ->delete();
    }

    public function form_active_apps($id)
    {
        $data["user"] = User::find($id);
        $data["sites"] = Site::orderby("name")->get();
        return view('users.form_active_apps', $data);
    }

    public function store_active_apps(Request $request)
    {
        $user = User::find($request->id);
        DB::table('active_app_user')
                ->where('user_id', $request->id)
                ->delete();
        $user->active_apps()->sync($request->active_apps);

    }

}

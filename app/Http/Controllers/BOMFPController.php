<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class BOMFPController extends Controller
{
    public function showView($id, $parentitemNo)
    {
        $response = $this->showJson($id, $parentitemNo);
        $jsonData = $response->getData(true); // array di oggetti componenti
        return view('popup.ordine-info', ['componenti' => $jsonData]);
    }

    public function showJson($id, $parentitemNo)
    {
        $componenti = DB::table('gua_items_in_producion_fp')
            ->where('mesOrderNo', $id)
            ->get();
        
        /* $componentiArray = $componenti->map(function ($item) {
            $componentNo = $item->componentNo;
            $giacenze = DB::table('qta_guala_pro_rom')
                ->select('Quantita', 'Stato_udc', 'productype', 'UM')
                ->where('sku', $componentNo)
                ->get();
        
            $item->productype = $giacenze->first->productype ?? null;
            $item->UM = $giacenze->first->UM;

            $qty_ds = $giacenze->filter(fn($row) => str_contains($row->Stato_udc, 'DS'))->sum('Quantita');
            $qty_ok = $giacenze->filter(fn($row) => str_contains($row->Stato_udc, 'OK Certificated'))->sum('Quantita');
            $qty_ss = $giacenze->filter(fn($row) => str_contains($row->Stato_udc, 'SS Suspended'))->sum('Quantita');

            // Aggiungiamo i dati se presenti
          
            $item->ds = $qty_ds;
            $item->ss = $qty_ss;
            $item->ok = $qty_ok;
            return $item;
        }); */
        
        $componentiArray = $componenti;

        return response()->json($componentiArray);
    }
}

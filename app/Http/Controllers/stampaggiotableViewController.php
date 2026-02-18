<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\DB;

class stampaggiotableViewController extends Controller
{   
    public function index()
    {
     $rows = DB::table('stampaggio_view')
        ->whereNotNull('mesOrderNo') // Rimuovi le righe dove mesOrderNo è NULL
        ->where('mesOrderNo', '!=', '') // Rimuovi le righe dove mesOrderNo è una stringa vuota
        ->whereNotNull('itemNo') // Rimuovi le righe dove itemNo è NULL
        ->where('itemNo', '!=', '') // Rimuovi le righe dove itemNo è una stringa vuota
        ->orderBy('machinePressFull')
        ->orderBy('GUAPosition')
        ->orderBy('relSequence')
        ->get();

        $grouped = [];
        $rows = $rows->sortBy('GUAPosition');
        foreach ($rows as $row) {
            $pdfPath = public_path("bolle_lavorazione_pdf/{$row->mesOrderNo}.pdf");
            $row->pdf_exists = file_exists($pdfPath);
            $row->machinePressFull = "Pr ".$row->GUAPosition."   -   ".$row->machinePressFull;
            $grouped[$row->machinePressFull][] = $row;
        }

        // Prepara un array piatto con righe fittizie per il gruppo
        $result = [];
        foreach ($grouped as $pressFull => $items) {
            foreach ($items as $item) {
                $item->is_group = false;
                $item->quantita_rimanente = $item->quantity - $item->quantita_prodotta;
                $result[] = $item;
            }
        }

        return response()->json(array_values($result));
    }


    public function updateCommento(Request $request)
    {
        DB::table('table_gua_mes_prod_orders')
            ->where('id', $request->input('id'))
            ->update(['commento' => $request->input('commento')]);

        return response()->json(['success' => true]);
    }

     public function stampa(Request $request)
    {
       $rows = DB::table('stampaggio_view')
        ->whereNotNull('mesOrderNo') // Rimuovi le righe dove mesOrderNo è NULL
        ->where('mesOrderNo', '!=', '') // Rimuovi le righe dove mesOrderNo è una stringa vuota
        ->whereNotNull('itemNo') // Rimuovi le righe dove itemNo è NULL
        ->where('itemNo', '!=', '') // Rimuovi le righe dove itemNo è una stringa vuota
        ->orderBy('machinePressFull')
        ->orderBy('GUAPosition')
        ->orderBy('relSequence')
        ->get();

        $grouped = [];
        $rows = $rows->sortBy('GUAPosition');
        foreach ($rows as $row) {
            $pdfPath = public_path("bolle_lavorazione_pdf/{$row->mesOrderNo}.pdf");
            $row->pdf_exists = file_exists($pdfPath);
            $row->machinePressFull = "Pr ".$row->GUAPosition."   -   ".$row->machinePressFull;
            $grouped[$row->machinePressFull][] = $row;
        }

        // Prepara un array piatto con righe fittizie per il gruppo
        $result = [];
        foreach ($grouped as $pressFull => $items) {
            foreach ($items as $item) {
                $item->is_group = false;
                $item->quantita_rimanente = $item->quantity - $item->quantita_prodotta;
                $result[] = $item;
            }
        }

        $title = 'Monitor Stampaggio';

        return view('app.APP1.PDF_Stampaggio.index', [
            'title' => $title,
            'righe' => $result,
        ]);
    }
}

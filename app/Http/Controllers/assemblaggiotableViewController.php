<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\DB;

class assemblaggiotableViewController extends Controller
{
    public function index()
    {
        $rows = DB::table('assemblaggio_view')
            ->whereNotNull('mesOrderNo')
            ->where('mesOrderNo', '!=', '')
            ->whereNotNull('itemNo')
            ->where('itemNo', '!=', '')
            ->orderBy('family')
            ->orderBy('nome_completo_macchina') // usa il nome completo
            ->orderBy('relSequence')
            ->get();

        // Raggruppa: family -> nome_completo_macchina -> ordini
        $grouped = [];
        foreach ($rows as $row) {
            $pdfPath = public_path("bolle_lavorazione_pdf/{$row->mesOrderNo}.pdf");
            $row->pdf_exists = file_exists($pdfPath);
            $grouped[$row->family][$row->nome_completo_macchina][] = $row;
        }

        $result = [];
        foreach ($grouped as $familyName => $machines) {
            $result[] = [
                'is_group' => true,
                'group_type' => 'family',
                'value' => $familyName
            ];

            foreach ($machines as $machineFullName => $items) {
                if($items[0]->nome_completo_macchina != "MPACK - PACKAGING"){
                    $result[] = [
                        'is_group' => true,
                        'group_type' => 'machine',
                        'family' => $familyName,
                        'value' => $items[0]->nome_completo_macchina,
                        'groupLabel' => $items[0]->nome_completo_macchina 
                    ];

                    foreach ($items as $item) {
                        $item->is_group = false;
                        $item->quantita_rimanente = $item->quantity - $item->quantita_prodotta;
                        $result[] = $item;
                    }
                }
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
        $rows = DB::table('assemblaggio_view')
            ->whereNotNull('mesOrderNo')->where('mesOrderNo', '!=', '')
            ->whereNotNull('itemNo')->where('itemNo', '!=', '')
            ->orderBy('family')
            ->orderBy('nome_completo_macchina')
            ->orderBy('relSequence')
            ->get();

        // family -> macchina (escludo MPACK - PACKAGING) + flag pdf
        $grouped = [];
        foreach ($rows as $row) {
            if ($row->nome_completo_macchina === 'MPACK - PACKAGING') {
                continue;
            }
            //$row->pdf_exists = File::exists(public_path("bolle_lavorazione_pdf/{$row->mesOrderNo}.pdf"));
            $grouped[$row->family][$row->nome_completo_macchina][] = $row;
        }

        // flatten come da tua struttura
        $result = [];
        foreach ($grouped as $familyName => $machines) {
            $result[] = [
                'is_group'   => true,
                'group_type' => 'family',
                'value'      => $familyName,
            ];
            foreach ($machines as $machineFullName => $items) {
                $result[] = [
                    'is_group'   => true,
                    'group_type' => 'machine',
                    'family'     => $familyName,
                    'value'      => $machineFullName,
                    'groupLabel' => $machineFullName,
                ];
                foreach ($items as $item) {
                    $item->is_group = false;
                    $item->quantita_rimanente = ($item->quantity ?? 0) - ($item->quantita_prodotta ?? 0);
                    $result[] = $item;
                }
            }
        }

        $title = 'Monitor Assemblaggio';

        return view('app.APP1.PDF.index', [
            'title' => $title,
            'righe' => $result,
        ]);
    }
}

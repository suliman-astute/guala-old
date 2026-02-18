<?php
namespace App\Http\Controllers;

use App\Models\ExtInfos;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller; 

class ImportController extends Controller
{
    public static function import()
    {
        // 1. Recupera ordini da MySQL
        $orders = DB::table('table_gua_mes_prod_orders')->select('id', 'mesOrderNo', 'itemNo')->get();

        foreach ($orders as $r) {
            $strToSearch = "RO-" . $r->itemNo;

            // 2. Query su BOMExplosion da sqlsrv2 (Data Warehouse)
            $bom = DB::connection('sqlsrv2')
                ->table(DB::raw('[shir].[dwh].[BOMExplosion]'))
                ->select('xLevel', 'productionBOMNo', 'BOMReplSystem', 'BOMInvPostGr', 'No', 'ReplSystem', 'InvPostGr', 'UoM', 'QtyPer', 'PercScarti', 'PathString', 'PathLength', 'StartingDate', 'Company')
                ->where('productionBOMNo', $strToSearch)
                ->where('xLevel', 1)
                ->get();

            // 3. Query su GoodQuantityMonitoring da sqlsrv1 (Romania)
            $good = DB::connection('sqlsrv1')
                ->table('GoodQuantityMonitoring')
                ->selectRaw('SUM(good) as total_good')
                ->where('ordername', $r->mesOrderNo)
                ->first();

            // 4. Update quantità prodotta in MySQL
            DB::table('table_gua_mes_prod_orders')
                ->where('id', $r->id)
                ->update([
                    'quantita_prodotta' => $good->total_good ?? 0
                ]);

            // 5. Inserimento BOMExplosion in MySQL
            foreach ($bom as $b) {
                DB::table('bom_explosion')->insert([
                    'xLevel' => $b->xLevel,
                    'productionBOMNo' => $b->productionBOMNo,
                    'BOMReplSystem' => $b->BOMReplSystem,
                    'BOMInvPostGr' => $b->BOMInvPostGr,
                    'No' => $b->No,
                    'ReplSystem' => $b->ReplSystem,
                    'InvPostGr' => $b->InvPostGr,
                    'UoM' => $b->UoM,
                    'QtyPer' => $b->QtyPer,
                    'PercScarti' => $b->PercScarti,
                    'PathString' => $b->PathString,
                    'PathLength' => $b->PathLength,
                    'StartingDate' => $b->StartingDate,
                    'Company' => $b->Company,
                ]);
            }
        }
    }
}

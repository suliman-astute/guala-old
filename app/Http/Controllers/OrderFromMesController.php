<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class OrderFromMesController extends Controller
{
    public function index()
    {
        $rows = DB::table('orderfrommes')->get(); // fa: SELECT * FROM orderfrommes
        return response()->json($rows);
    }


}

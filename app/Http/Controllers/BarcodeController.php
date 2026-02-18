<?php

namespace App\Http\Controllers;

use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeController extends Controller
{
    public function code128($code)
    {
        
        $code = urldecode($code);
        
        $generator = new BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($code, $generator::TYPE_CODE_128);

        return response($barcode)->header('Content-type', 'image/png');
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RFCController extends Controller
{
public function validarRFC_API(Request $request)
{
    $rfc = $request->input('rfc');

    return response()->json([
        'mensaje' => 'RFC recibido: ' . $rfc
    ]);
}
}

<?php

namespace Modules\Api\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \BC\QrCode\Facades\QrCode;

class QRController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    
    public function generate(Request $request)
    {
        $request->validate([
            'data' => 'required',
            'size' => 'required|numeric',
        ]);

        return QrCode::size($request->size)->generate($request->data);
    }
}

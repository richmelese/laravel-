<?php
namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;

class CountryController extends Controller
{
    public function index()
    {
        return response()->json([
            'data'=>get_country_lists()
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function index($lang) {
        $trans_file = public_path('json/'.$lang.'.json');

        $trans = json_decode(file_get_contents($trans_file));

        return response()->json(['trans' => $trans, 'code' => 200]);
    }
}

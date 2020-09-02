<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index() {
        try {
            $settings = DB::table('settings')->get();

            $result = [];
            foreach($settings as $row) {
                $result[$row->name] = $row->value;
            }
            return response()->json([
                'status'    =>  'success',
                'result'     => $result,
                'message'   =>  'Settings'
            ], 200);

        } catch (\ErrorException $ex) {
            return response()->json([
                'status'    =>  'error',
                'result'     => null,
                'message'   =>  'Exception'
            ], 404);
        } catch( \Illuminate\Database\QueryException $qe) {
            return response()->json([
                'status'    =>  'error',
                'result'     => null,
                'message'   =>  $qe->errorInfo
            ], 400);
        }
    }

    public function update(Request $request)
    {
        $params = $request->all();

        try {
            foreach($params as $key => $value) {
                DB::table('settings')->where('name', $key)->update(['value' => $value]);
            }

            return response()->json([
                'status'    =>  'success',
                'result'     => $params,
                'message'   =>  'Settings Updated'
            ], 200);
        } catch (\ErrorException $ex) {
            return response()->json([
                'status'    =>  'error',
                'result'     => null,
                'message'   =>  'Exception'
            ], 404);
        } catch( \Illuminate\Database\QueryException $qe) {
            return response()->json([
                'status'    =>  'error',
                'result'     => null,
                'message'   =>  $qe->errorInfo
            ], 400);
        }
    }
}

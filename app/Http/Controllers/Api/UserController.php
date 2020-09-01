<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Providers\RouteServiceProvider;
use Validator;
use App\User;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $params = $request->all();

        if (Auth::attempt(['email' => $params['email'], 'password' => $params['password']])) {
            $user = Auth::user();
            $token =  $user->createToken('AppName')->accessToken;

            return response()->json([
                'status'    =>  'success',
                'token'     => $token,
                'message'   =>  'Authorized'
            ], 200);
        } else {
            return response()->json([
                'status'    =>  'error',
                'token'     => null,
                'message'   =>   'Unauthorized'
            ], 401);
        }
    }

    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|email',
                'password' => 'required',
                'c_password' => 'required|same:password',
            ]);

            if ($validator->fails()) {
                return response()->json(['error'=>$validator->errors()], 401);
            }

            $input = $request->all();
            $input['password'] = bcrypt($input['password']);
            $user = User::create($input);
            $token =  $user->createToken('AppName')-> accessToken;

            return response()->json([
                    'status'    =>  'success',
                    'token'     => $token,
                    'message'   =>  'Registered'
                ], 200);

        } catch (\ErrorException $ex) {
            return response()->json([
                'status'    =>  'error',
                'token'     => null,
                'message'   =>  'Exception'
            ], 400);
        } catch( \Illuminate\Database\QueryException $qe) {
            return response()->json([
                'status'    =>  'error',
                'token'     => null,
                'message'   =>  $qe->errorInfo
            ], 400);
        }
    }
}
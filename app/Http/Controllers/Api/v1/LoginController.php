<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\DeliveryUser;

class LoginController extends Controller
{
    //
    public function login(Request $request)
    {
        # code...
        $loginCredentials = $request->validate([
            "email" => "required|string",
            "password" => "required|string"
        ]);

        $params = $request->all();

        $loginCredentials['active'] = true;
        $loginCredentials['deleted_at'] = null;
        $loginCredentials['type'] = env('USERS_TYPE');

        if (!Auth::attempt($loginCredentials)) {
            return response([
                "status"  => false,
                "message" => "Credenciales incorrectas. Intenta nuevamente. Verifica tu usuario y contraseña.",
                "body"    => null,
                "redirect" => false
            ], 400);
        }

        $accessToken = Auth::user()->createToken('authToken')->accessToken;
        $user = User::with('activeLicense')->find(Auth::user()->id);
        if (isset($params['firebase_token'])) {
            $user->firebase_token = $params['firebase_token'];
            $user->save();
        }
        $user->access_token = $accessToken;
        return response([
            "status" => true,
            "message" => "Successfully login. Welcome!",
            "body" => $user,
            "redirect" => false,
            "access_token" => $accessToken
        ]);
    }

    public function logout()
    {
        $userToken = Auth::user()->token();
        $userToken->revoke();
        return response()->json([
                "status"  => true,
                'message' => 'Successfully logged out from current session',
                "body" => null,
                "redirect" => true
            ]);
    }

    public function logoutAll()
    {
        $userTokens = Auth::user()->tokens;
        foreach ($userTokens as $key => $token) {
            $token->revoke();
        }
        return response()->json([
                "status"  => true,
                'message' => 'Successfully logged out from all sessions',
                "body" => null,
                "redirect" => true
            ]);
    }
}

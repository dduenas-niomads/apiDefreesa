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

        $loginCredentials['active'] = true;
        $loginCredentials['deleted_at'] = null;

        if (!Auth::attempt($loginCredentials)) {
            return response([
                "status"  => false,
                "message" => "Invalid login credentials",
                "body"    => null,
                "redirect" => false
            ], 400);
        }

        $accessToken = Auth::user()->createToken('authToken')->accessToken;
        $user = User::with('activeLicense')->find(Auth::user()->id);
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

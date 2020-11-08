<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use App\Models\LicensePrUser;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Notifications\SignupActivate;
use App\Notifications\ForgotPassword;
use App\Notifications\DefaultPasswordChanged;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(Request $request)
    {
        $params = $request->all();
        // USER
        if (isset($params['email'])) {
            $user = User::where('email', $params['email'])->first();
        }
        if (is_null($user)) {
            $user = User::create([
                'name' => $params['name'],
                'lastname' => $params['lastname'],
                'email' => $params['email'],
                'type_document' => $params['type_document'],
                'document_number' => $params['document_number'],
                'phone' => isset($params['phone']) ? $params['phone'] : null,
                'password' => Hash::make($params['password']),
                'activation_token' => Str::random(60)
            ]);
            if (isset($params['type']) && (int)$params['type'] === 2) {
                # llamar al servicio crear delivery_user
            }
            if (isset($params['type']) && (int)$params['type'] === 3) {
                # llamar al servicio crear supplier_user
            }
            // LICENSE PER USER
            $licensePrUser = new LicensePrUser();
            $licensePrUser->users_id = $user->id;
            $licensePrUser->licenses_id = 1;
            $licensePrUser->date_start = date("Y-m-d");
            $licensePrUser->date_end = null;
            $licensePrUser->status = 1;
            $licensePrUser->save();
    
            try {
                $user->notify(new SignupActivate($user));
                $user->email_verified_at = date("Y-m-d H:i:s");
                $user->save();
            } catch (\Throwable $th) {
                throw $th;
            }

            return response()->json([
                'status'  => true,
                'message' => 'Nice! Your account is created. Please check your email account to validate your account :)',
                'body'    => null,
                'redirect' => false
            ], 200);
        } else {
            return response()->json([
                'status'  => false,
                'message' => 'Your account cannot be created. This email account already exists',
                'body'    => null,
                'redirect' => false
            ], 400);
        }
    }

    public function signupActivate($token)
    {
        $user = User::where('activation_token', $token)->first();
        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'This activation token is invalid.',
                'body'    => null,
                'redirect' => false
            ], 404);
        }
        $user->active = true;
        $user->activation_token = null;
        $user->save();

        return view('welcome', compact('user'));

        // return response()->json([
        //     'status'  => true,
        //     'message' => 'Nice! Your account is active. Please go to niomads.com to login :)',
        //     'body'    => null,
        //     'redirect' => false
        // ], 200);
    }

    public function forgotPassword(Request $request)
    {
        $params = $request->all();
        $user = User::whereNull('deleted_at')
            ->where('active', true)
            ->where('email', $params['email'])
            ->first();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'This user not exist.',
                'body'    => null,
                'redirect' => false
            ], 404);
        }

        try {
            $user->forgot_password_token = Str::random(60);
            $user->save();
            $user->notify(new ForgotPassword($user));
        } catch (\Throwable $th) {
            throw $th;
        }

        return response()->json([
            'status'  => true,
            'message' => 'Mail sended to ' . $params['email'] . '. Check your inbox to change your password.',
            'body'    => null,
            'redirect' => false
        ], 200);
    }

    public function forgotPasswordActive($token)
    {
        $user = User::where('forgot_password_token', $token)->first();
        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'This password token is invalid.',
                'body'    => null,
                'redirect' => false
            ], 404);
        }

        try {
            $user->password = Hash::make(env('DEFAULT_PASSWORD'));
            $user->save();
            $user->notify(new DefaultPasswordChanged($user));
        } catch (\Throwable $th) {
            throw $th;
        }

        return response()->json([
            'status'  => true,
            'message' => 'Nice! Your new password is ' . env('DEFAULT_PASSWORD') . '. Please, log in and change your password. Please go to niomads.com to login :)',
            'body'    => null,
            'redirect' => false
        ], 200);
    }
}

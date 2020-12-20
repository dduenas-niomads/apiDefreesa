<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use App\DeliveryUser;
use App\Consumer;
use App\Partner;
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
                'type' => isset($params['type']) ? (int)$params['type'] : 1,
                'document_number' => $params['document_number'],
                'phone' => isset($params['phone']) ? $params['phone'] : null,
                'password' => Hash::make($params['password']),
                'activation_token' => Str::random(60)
            ]);
            if (isset($params['type']) && (int)$params['type'] === 1) {
                $consumer = new Consumer();
                $consumer->users_id = $user->id;
                $consumer->name = $user->name;
                $consumer->lastname = $user->lastname;
                $consumer->email = $user->email;
                $consumer->phone = $user->phone;
                $consumer->type_document = $user->type_document;
                $consumer->document_number = $user->document_number;
                $consumer->password = $user->password;
                $consumer->save();
            }            
            if (isset($params['type']) && (int)$params['type'] === 2) {
                $deliveryUser = new DeliveryUser();
                $deliveryUser->users_id = $user->id;
                $deliveryUser->name = $user->name;
                $deliveryUser->lastname = $user->lastname;
                $deliveryUser->email = $user->email;
                $deliveryUser->phone = $user->phone;
                $deliveryUser->type_document = $user->type_document;
                $deliveryUser->document_number = $user->document_number;
                $deliveryUser->password = $user->password;
                $deliveryUser->activation_token = $user->activation_token;
                $deliveryUser->save();
            }
            if (isset($params['type']) && (int)$params['type'] === 3) {
                $partner = new Partner();
                $partner->users_id = $user->id;
                $partner->name = $user->name;
                $partner->phone = $user->phone;
                $partner->ruc = $user->document_number;
                $partner->password = $user->password;
                $partner->save();
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
                'message' => '¡Genial! Tu cuenta ha sido creada. Por favor, revisa tu correo electrónico para activar tu cuenta.',
                'body'    => null,
                'redirect' => false
            ], 200);
        } else {
            return response()->json([
                'status'  => false,
                'message' => 'Tu cuenta no puede ser creada. Este correo ya existe actualmente en Defreesa',
                'body'    => null,
                'redirect' => false
            ], 400);
        }
    }

    public function getSms(Request $request)
    {
        $params = $request->all();
        // USER
        if (isset($params['phone'])) {
            return response()->json([
                'status'  => true,
                'message' => 'Se envió un código en un SMS al número ' . $params['phone'],
                'body'    => [
                    "code" => 102030,
                    "size" => 6
                ],
                'redirect' => false
            ], 200);            
        } else {
            return response()->json([
                'status'  => false,
                'message' => 'No se pudo enviar el código SMS. No ingresaste número de teléfono',
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
                'message' => 'El token de activación es inválido',
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

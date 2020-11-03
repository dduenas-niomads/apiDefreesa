<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\DeliveryUser;

class DeliveryUserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        if (!is_null($user)) {
            $deliveryUser = DeliveryUser::whereNull('deleted_at')->get();
            return response([
                "status" => !empty($deliveryUser) ? true : false,
                "message" => !empty($deliveryUser) ? "list of categories" : "categories not found",
                "body" => $deliveryUser,
                "redirect" => false
            ], 200);
        } else {
            return response([
                "message" => "forbidden",
                "body" => null
            ], 403);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!is_null($user)) {
            $request->validate([
                'name'=>'required',
                'email'=>'required',
                'password'=>'required'
            ]);
            $deliveryUser = new DeliveryUser([
                'name' => $request->get('name'),
                'last_name' => $request->get('last_name'),
                'email' => $request->get('email'),
                'password' => Hash::make($request['password'])
            ]);
            $deliveryUser->save();
            return response([
                    "message" => "delivery user created",
                    "body" => $deliveryUser
                ], 201);
            }else {
                return response([
                    "message" => "cannot create delivery user",
                    "body" => null
                ], 403);
            }    
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function show($id)
    {
        $user = Auth::user();
        if (!is_null($user)) {
            $deliveryUser = DeliveryUser::find($id);
            if (!is_null($deliveryUser)) {
                return response([
                    "message" => "found delivery User",
                    "body" => $deliveryUser
                ], 200);
            } else {
                return response([
                    "message" => "delivery User not found",
                    "body" => $deliveryUser
                ], 404);
            }
        } else {
            return response([
                "message" => "forbidden",
                "body" => null
            ], 403);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // 
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id, Request $request)
    {
        $user = Auth::user();
        if (!is_null($user)) {
            $deliveryUser = DeliveryUser::find($id);
            if (!is_null($deliveryUser)) {
                $params = $request->all();
                $deliveryUser->fill($params);
                $deliveryUser->save();
                return response([
                    "message" => !empty($deliveryUser) ? "Repartidor actualizado correctamente" : "Delivery user not found",
                    "body" => $deliveryUser
                ], 200);
            } else {
                return response([
                    "message" => !empty($deliveryUser) ? "Repartidor actualizado correctamente" : "Delivery user not found",
                    "body" => $deliveryUser
                ], 404);
            }
        } else {
            return response([
                "message" => "forbidden",
                "body" => null
            ], 403);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    
}

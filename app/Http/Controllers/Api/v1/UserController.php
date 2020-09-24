<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        $user = User::with('activeLicense')->find(Auth::user()->id);
        return response([
            "status" => !is_null($user) ? true : false,
            "message" => !is_null($user) ? "found user" : "user not found",
            "body" => $user ,
            "redirect" => false
        ]);
    }

    public function showById($id)
    {
        $user = User::with('activeLicense')->find($id);
        return response([
            "status" => !is_null($user) ? true : false,
            "message" => !is_null($user) ? "found user" : "user not found",
            "body" => $user,
            "redirect" => false
        ]);
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
    public function update(Request $request)
    {
        $user = Auth::user();
        $params = $request->all();
        if (isset($params['null_validation']) && $params['null_validation']) {
            $params = array_filter($params);
        }
        
        if (isset($params['email'])) {
            unset($params['email']);
        }
        if (isset($params['password'])) {
            $params['password'] = Hash::make($params['password']);
        }
        $user->fill($params);
        $user->save();
        return response([
            "status" => !is_null($user) ? true : false,
            "message" => !is_null($user) ? "updated user" : "user not updated",
            "body" => $user,
            "redirect" => false
        ]);
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

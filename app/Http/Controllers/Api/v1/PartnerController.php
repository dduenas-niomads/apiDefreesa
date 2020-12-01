<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Partner;
use App\User;

class PartnerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!is_null($user)) {
            $params = $request->all();
            $partner = Partner::whereNull('deleted_at')
                        ->paginate(env('ITEMS_PAGINATOR'));
            return response([
                "status" => !empty($partner) ? true : false,
                "message" => !empty($partner) ? "list of partners" : "partners not found",
                "body" => $partner,
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function show($id)
    {
        //
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
            $partner = Partner::find($id);
            if (!is_null($partner)) {
                $params = $request->all();
                $partner->fill($params);
                $partner->save();
                return response([
                    "status" => !empty($partner) ? true : false,
                    "message" => !empty($partner) ? "Partner actualizado correctamente" : "partner not found",
                    "body" => $partner,
                    "redirect" => false
                ], 200);
            } else {
                return response([
                    "status" => !empty($partner) ? true : false,
                    "message" => !empty($partner) ? "Partner actualizado correctamente" : "partner not found",
                    "body" => $partner,
                    "redirect" => false
                ], 404);
            }
        } else {
            return response([
                "status" => false,
                "message" => "forbidden",
                "body" => null,
                "redirect" => true
            ], 403);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        $user = Auth::user();
        if (!is_null($user)) {
            $partners = Partner::find($id);
            if (!is_null($partners)) {
                $params = $request->all();
                $partners->active = Partner::STATE_INACTIVE;
                $partners->deleted_at = date("Y-m-d H:i:s");
                $partners->save();
                return response([
                    "status" => !empty($partners) ? true : false,
                    "message" => !empty($partners) ? "Partner eliminado correctamente" : "partners not found",
                    "body" => $partners,
                    "redirect" => false
                ], 200);
            } else {
                return response([
                    "status" => !empty($partners) ? true : false,
                    "message" => !empty($partners) ? "Partner eliminado correctamente" : "partners not found",
                    "body" => $partners,
                    "redirect" => false
                ], 404);
            }
        } else {
            return response([
                "status" => false,
                "message" => "forbidden",
                "body" => null,
                "redirect" => true
            ], 403);
        }
    }   
}

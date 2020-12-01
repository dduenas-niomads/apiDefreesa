<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Consumer;
use App\User;

class ConsumerController extends Controller
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
            $consumer = Consumer::whereNull('deleted_at')
                        ->paginate(env('ITEMS_PAGINATOR'));
            return response([
                "status" => !empty($consumer) ? true : false,
                "message" => !empty($consumer) ? "list of consumers" : "consumers not found",
                "body" => $consumer,
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
            $consumer = Consumer::find($id);
            if (!is_null($consumer)) {
                $params = $request->all();
                $consumer->fill($params);
                $consumer->save();
                return response([
                    "status" => !empty($consumer) ? true : false,
                    "message" => !empty($consumer) ? "Consumidor actualizado correctamente" : "consumer not found",
                    "body" => $consumer,
                    "redirect" => false
                ], 200);
            } else {
                return response([
                    "status" => !empty($consumer) ? true : false,
                    "message" => !empty($consumer) ? "Consumidor actualizado correctamente" : "consumer not found",
                    "body" => $consumer,
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
            $consumers = Consumer::find($id);
            if (!is_null($consumers)) {
                $params = $request->all();
                $consumers->active = Consumer::STATE_INACTIVE;
                $consumers->deleted_at = date("Y-m-d H:i:s");
                $consumers->save();
                return response([
                    "status" => !empty($consumers) ? true : false,
                    "message" => !empty($consumers) ? "Consumidor eliminado correctamente" : "consumers not found",
                    "body" => $consumers,
                    "redirect" => false
                ], 200);
            } else {
                return response([
                    "status" => !empty($consumers) ? true : false,
                    "message" => !empty($consumers) ? "Consumidor eliminado correctamente" : "consumers not found",
                    "body" => $consumers,
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

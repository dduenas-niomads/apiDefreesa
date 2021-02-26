<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Ranking;
use App\Models\Order;

class RankingController extends Controller
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
            $rankings = Ranking::whereNull('deleted_at');
            if (isset($params['orderBy']) && !is_null($params['orderBy'])) {
                $rankings = $rankings->orderBy($params['orderBy'], $params['orderDir']);
            }
            $rankings = $rankings->paginate(env('ITEMS_PAGINATOR'));
            return response([
                "status" => !empty($rankings) ? true : false,
                "message" => !empty($rankings) ? "list of rankings" : "rankings not found",
                "body" => $rankings,
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
        return null;
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
            $params = $request->all();
            $params['users_id'] = $user->id;
            if (isset($params['bs_orders_id'])) {
                $order = Order::find((int)$params['bs_orders_id']);
                if (!is_null($order)) {
                    $order->flag_ranking_needed = Order::STATE_INACTIVE;
                    $order->save();
                    $ranking = new Ranking();
                    $ranking = $ranking->create($params);
                    return response([
                        "status" => !empty($ranking) ? true : false,
                        "message" => !empty($ranking) ? "Gracias por tu puntuación!" : "No se pudo crear la puntuación",
                        "body" => $ranking,
                        "redirect" => false
                    ], 201);
                } else {
                    return response([
                        "status" => false,
                        "message" => "La orden ingresada no es válida",
                        "body" => null,
                        "redirect" => true
                    ], 400);
                }
            } else {
                return response([
                    "status" => false,
                    "message" => "Debe ingresar una orden",
                    "body" => null,
                    "redirect" => true
                ], 400);
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return null;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return null;
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
        return response([
            "status" => false,
            "message" => "forbidden",
            "body" => null,
            "redirect" => true
        ], 403);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        return response([
            "status" => false,
            "message" => "forbidden",
            "body" => null,
            "redirect" => true
        ], 403);
    }
}

<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

class OrderController extends Controller
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
            $orders = Order::whereNull(Order::TABLE_NAME . '.deleted_at')
                ->where(Order::TABLE_NAME . '.users_id', $user->id)
                ->orderBy(Order::TABLE_NAME . '.created_at', 'DESC')
                ->paginate(env('ITEMS_PAGINATOR'));
            return response([
                "status" => !empty($orders) ? true : false,
                "message" => !empty($orders) ? "list of orders" : "orders not found",
                "body" => $orders,
                "redirect" => false
            ], 200);
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
            if (isset($params['details_info']) && is_array($params['details_info'])) {
                $params['bs_suppliers_id'] = 0;
                foreach ($params['details_info'] as $key => $value) {
                    $params['bs_suppliers_id'] = $value['bs_suppliers_id'];
                }
            }
            $order = Order::create($params);
            if (isset($params['address_info'])) {
                $user->address_info = $params['address_info'];
                $user->save();
            }
            return response([
                "status" => !empty($order) ? true : false,
                "message" => !empty($order) ? "created order" : "order cannot be created",
                "body" => $order,
                "redirect" => false
            ], 201);
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
        $user = Auth::user();
        if (!is_null($user)) {
            $order = Order::where(Order::TABLE_NAME . '.users_id', $user->id)
                ->orderBy(Order::TABLE_NAME . '.created_at', 'DESC')
                ->with('supplier')
                ->with('customer')
                ->with('orderStatus')
                ->find($id);
            return response([
                "status" => !empty($order) ? true : false,
                "message" => !empty($order) ? "find order" : "order not found",
                "body" => $order,
                "redirect" => false
            ], 200);
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
        $user = Auth::user();
        if (!is_null($user)) {
            $order = Order::where(Order::TABLE_NAME . '.users_id', $user->id)
                ->find($id);
            if (!is_null($order)) {
                $params = $request->all();
                if (isset($params['purchase_info'])) {
                    if (isset($params['success']) && (int)$params['success'] === 1) {
                        $order->purchase_info = $params['purchase_info'];
                        $order->status = Order::STATUS_PROCEED;
                        $order->save();
                    } else {
                        $order->purchase_info = $params['purchase_info'];
                        $order->status = Order::STATUS_NOT_PROCEED;
                        $order->save();
                    }
                }
                return response([
                    "status" => !empty($order) ? true : false,
                    "message" => !empty($order) ? "find order" : "order not found",
                    "body" => $order,
                    "redirect" => false
                ], 200);
            } else {
                return response([
                    "status" => !empty($order) ? true : false,
                    "message" => !empty($order) ? "find order" : "order not found",
                    "body" => $order,
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

    public function deliveryNextStatus($id, Request $request)
    {
        $order = Order::find($id);
        if (!is_null($order)) {
            $params = $request->all();
            if (isset($params['purchase_info'])) {
                if (isset($params['success']) && (int)$params['success'] === 1) {
                    $order->purchase_info = $params['purchase_info'];
                    $order->status = Order::STATUS_PROCEED;
                    $order->save();
                } else {
                    $order->purchase_info = $params['purchase_info'];
                    $order->status = Order::STATUS_NOT_PROCEED;
                    $order->save();
                }
            }
            return response([
                "status" => !empty($order) ? true : false,
                "message" => !empty($order) ? "find order" : "order not found",
                "body" => $order,
                "redirect" => false
            ], 200);
        } else {
            return response([
                "status" => !empty($order) ? true : false,
                "message" => !empty($order) ? "order updated" : "order not found",
                "body" => $order,
                "redirect" => false
            ], 404);
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
            $order = Order::whereNull(Order::TABLE_NAME . '.deleted_at')
                ->where(Order::TABLE_NAME . '.users_id', $user->id)
                ->orderBy(Order::TABLE_NAME . '.created_at', 'DESC')
                ->find($id);
            $status = 404;
            if (!is_null($order)) {
                $status = 200;
                $params = $request->all();
                $order->commentary = isset($params['commentary']) ? $params['commentary'] : null;
                $order->status = 5;
                $order->flag_active = Order::STATE_INACTIVE;
                $order->save();
            }
            return response([
                "status" => !empty($order) ? true : false,
                "message" => !empty($order) ? "deleted order" : "order not found",
                "body" => $order,
                "redirect" => false
            ], $status);
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

<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use App\Models\Order;

class PaymentsController extends Controller
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
            $payments = Payment::whereNull(Payment::TABLE_NAME . '.deleted_at')
                ->where(Payment::TABLE_NAME . '.users_id', $user->id)
                ->with('supplier')
                ->with('customer')
                ->with('orderStatus')
                ->OrderBy(Payment::TABLE_NAME . '.created_at', 'DESC')
                ->paginate(env('ITEMS_PAGINATOR'));
            return response([
                "status" => !empty($payments) ? true : false,
                "message" => !empty($payments) ? "list of payments" : "payments not found",
                "body" => $payments,
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
            $params = $request->all();
            $params['users_id'] = $user->id;
            $params['acl_delivery_users_id'] = $user->id;
            $payments = new Payment();
            $payments = $payments->create($params);
            return response([
                "status" => !empty($payments) ? true : false,
                "message" => !empty($payments) ? "Pago creado" : "No se pudo crear el Pago",
                "body" => $payments,
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
            $payments = Payment::where(Payment::TABLE_NAME . '.users_id', $user->id)
                ->with('supplier')
                ->with('customer')
                ->with('orderStatus')
                ->find($id);
            return response([
                "status" => !empty($payments) ? true : false,
                "message" => !empty($payments) ? "find payment" : "payment not found",
                "body" => $payments,
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
            $payments = Payment::find($id);
            if (!is_null($payments)) {
                $params = $request->all();
                $payments->fill($params);
                $payments->save();
                return response([
                    "status" => !empty($payments) ? true : false,
                    "message" => !empty($payments) ? "Pago actualizado correctamente" : "payments not found",
                    "body" => $payments,
                    "redirect" => false
                ], 200);
            } else {
                return response([
                    "status" => !empty($payments) ? true : false,
                    "message" => !empty($payments) ? "Pago actualizado correctamente" : "payments not found",
                    "body" => $payments,
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
            $payments = Payment::find($id);
            if (!is_null($payments)) {
                $params = $request->all();
                $payments->flag_active = Payment::STATE_INACTIVE;
                $payments->deleted_at = date("Y-m-d H:i:s");
                $payments->save();
                return response([
                    "status" => !empty($payments) ? true : false,
                    "message" => !empty($payments) ? "Pago eliminado correctamente" : "payment not found",
                    "body" => $payments,
                    "redirect" => false
                ], 200);
            } else {
                return response([
                    "status" => !empty($payments) ? true : false,
                    "message" => !empty($payments) ? "Pago eliminado correctamente" : "payment not found",
                    "body" => $payments,
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

    public function myFounds(Request $request)
    {
        $user = Auth::user();
        if (!is_null($user)) {
            $params = $request->all();
            $total = 0;
            $list = [];
            $orders = Order::whereNull(Order::TABLE_NAME . '.deleted_at')
                ->where(Order::TABLE_NAME . '.bs_delivery_id', $user->id)
                ->whereNotIn(Order::TABLE_NAME . '.status', [5,6]);
            if (isset($params['date'])) {
                $orders = $orders->where(Order::TABLE_NAME . '.created_at', 'LIKE', '%' . $params['date'] . '%');
            }
            $orders = $orders->with('supplier')
                ->with('customer')
                ->with('orderStatus')
                ->orderBy(Order::TABLE_NAME . '.created_at', 'DESC')
                ->get();

            foreach ($orders as $key => $value) {
                $total = $total + $value->delivery_amount + $value->tips;
                array_push($list, [
                    "id" => $value->id,
                    "created_at" => $value->created_at,
                    "operation_supplier" => $value->supplier->name,
                    "operation_customer" => $value->customer->name,
                    'status_id'=> $value->orderStatus->id, 
                    'status_name'=> $value->orderStatus->name, 
                    'amount'=> $value->delivery_amount + $value->tips, 
                    'currency'=> 'PEN'
                ]);
            }
            
            $responseJson = ['balance' => $total, 
                'pending' => $total, 
                'list' => $list
            ];

            return response()->json([
                "status" => true,
                "message" => "list of founds",
                "body" => $responseJson,
                "redirect" => false
            ]);
        } else {
            return response([
                "message" => "forbidden",
                "body" => null
            ], 403);
        }
    }
}

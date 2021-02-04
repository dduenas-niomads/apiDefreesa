<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\MsOrderStatus;
use Kreait\Laravel\Firebase\Facades\Firebase;
use App\DeliveryUser;

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
                ->with('supplier')
                ->with('orderStatus')
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

    public function getListForPartners(Request $request)
    {
        $user = Auth::user();
        if (!is_null($user)) {
            $params = $request->all();
            $orders = Order::join(Supplier::TABLE_NAME, Supplier::TABLE_NAME . '.id', '=',
                   Order::TABLE_NAME . '.bs_suppliers_id')
                ->select(Order::TABLE_NAME . '.*')
                ->whereNull(Order::TABLE_NAME . '.deleted_at')
                ->with('supplier')
                ->with('customer')
                ->with('orderStatus')
                ->where(Supplier::TABLE_NAME . '.acl_partner_users_id', '=', $user->id);
            if (isset($params['date']) && $params['date'] !== "") {
                $orders = $orders->where(Order::TABLE_NAME . '.created_at', 'like', '%' . $params['date'] . '%');
            }
            if (isset($params['orderBy']) && !is_null($params['orderBy'])) {
                $orders = $orders->orderBy($params['orderBy'], $params['orderDir']);
            } else {
                $orders = $orders->orderBy(Order::TABLE_NAME . '.created_at', 'DESC');
            }
            if (isset($params['search']) && !is_null($params['search'])) {
                $key = $params['search'];
                // $orders = $orders->where(function($query) use ($key){
                //     $query->where(Order::TABLE_NAME . '.correlative', 'LIKE', '%' . $key . '%');
                //     $query->orWhere(Order::TABLE_NAME . '.reference', 'LIKE', '%' . $key . '%');
                //     $query->orWhere(MsOrderStatus::TABLE_NAME . '.status_code', 'LIKE', '%' . $key . '%');
                //     $query->orWhere(MsOrderStatus::TABLE_NAME . '.status_name', 'LIKE', '%' . $key . '%');
                // });
            }
            $orders = $orders->paginate(env('ITEMS_PAGINATOR'));
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

    public function getListMyOrders(Request $request)
    {
        $user = Auth::user();
        if (!is_null($user)) {
            $params = $request->all();
            $orders = Order::whereNull(Order::TABLE_NAME . '.deleted_at')
                ->with('supplier')
                ->with('customer')
                ->with('orderStatus')
                ->where(Order::TABLE_NAME . '.bs_delivery_id', $user->id);
            if (isset($params['date']) && $params['date'] !== "") {
                $orders = $orders->where(Order::TABLE_NAME . '.created_at', 'like', '%' . $params['date'] . '%');
            }
            $orders = $orders->orderBy(Order::TABLE_NAME . '.created_at', 'DESC')->get();
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
            $params['bs_delivery_id'] = $this->findDeliveryGuy($params);
            $order = Order::create($params);
            if (isset($params['address_info'])) {
                $user->address_info = $params['address_info'];
                $user->save();
            }
            // Create in firebase
            $this->createOrderInFirebase($order);
            // Create in firebase
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

    public function findDeliveryGuy($params = [])
    {
        $idDeliveryUser = 0;
        $deliveryUser = DeliveryUser::whereNull(DeliveryUser::TABLE_NAME . '.deleted_at')
            ->where(DeliveryUser::TABLE_NAME . '.active', '1')
            ->first();

        if (!is_null($deliveryUser)) {
            $idDeliveryUser = $deliveryUser->users_id;
        }
        return $idDeliveryUser;
    }

    public function createOrderInFirebase($order)
    {
        # code...
        firebase.database().ref('customers/' + userID).set({
            name: name,
            email: email,
        });

        $database->getReference('customers/' . $order->users_id)->set([
            'orderId' => $order->id,
           ]);
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
                ->with('supplier')
                ->with('customer')
                ->with('orderStatus')
                ->find($id);
            return response([
                "status" => !empty($order) ? true : false,
                "message" => !empty($order) ? "find order" : "Usted no cuenta con órdenes",
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

    public function showMainOrder(Request $request)
    {
        $user = Auth::user();
        if (!is_null($user)) {
            $order = Order::whereNotIn('status', [5,6])
                ->with('supplier')
                ->with('customer')
                ->with('orderStatus')
                ->orderBy('created_at', 'DESC')
                ->first();
            if (!is_null($order)) {
                $msOrderStatus = MsOrderStatus::find($order->status + 1);
                $order->order_next_status = $msOrderStatus;
            }
            return response([
                "status" => !empty($order) ? true : false,
                "message" => !empty($order) ? "find order" : "No tienes órdenes pendientes",
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
        $order = Order::with('supplier')
            ->with('customer')
            ->with('orderStatus')
            ->find($id);

        if (!is_null($order)) {
            if ($order->status !== Order::STATUS_FINAL) {
                $order->status = $order->status + 1;
                $order->save();
                if (!is_null($order)) {
                    $msOrderStatus = MsOrderStatus::find($order->status + 1);
                    $order->order_next_status = $msOrderStatus;
                }
            }
            return response([
                "status" => !empty($order) ? true : false,
                "message" => !empty($order) ? "order updated" : "order not found",
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

    public function declineOrder($id, Request $request)
    {
        $user = Auth::user();
        if (!is_null($user)) {
            $params = $request->all();
            $order = Order::join(Supplier::TABLE_NAME, Supplier::TABLE_NAME . '.id', '=',
                   Order::TABLE_NAME . '.bs_suppliers_id')
                ->select(Order::TABLE_NAME . '.*')
                ->whereNull(Order::TABLE_NAME . '.deleted_at')
                ->with('supplier')
                ->with('customer')
                ->with('orderStatus')
                ->where(Supplier::TABLE_NAME . '.acl_partner_users_id', '=', $user->id)
                ->find($id);
            if (isset($params['date']) && $params['date'] !== "") {
                $order = $order->where(Order::TABLE_NAME . '.created_at', 'like', '%' . $params['date'] . '%');
            }
            $status = 404;
            if ($order->delivery_status == Order::STATUS_STARTED) {
                $status = 200;
                $params = $request->all();
                $order->commentary = isset($params['commentary']) ? $params['commentary'] : null;
                $order->delivery_status = Order::STATUS_DECLINED;
                $order->save();
                return response([
                    "status" => !empty($order) ? true : false,
                    "message" => !empty($order) ? "Orden Rechazada Correctamente" : "No se encontró la Orden",
                    "body" => $order,
                    "redirect" => false
                ], 200);
            } else {
                return response([
                    "status" => !empty($order) ? true : false,
                    "message" => !empty($order) ? "No se puede rechazar la Orden" : "No se encontró la Orden",
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

    public function acceptOrder($id, Request $request)
    {
        $user = Auth::user();
        if (!is_null($user)) {
            $params = $request->all();
            $order = Order::join(Supplier::TABLE_NAME, Supplier::TABLE_NAME . '.id', '=',
                   Order::TABLE_NAME . '.bs_suppliers_id')
                ->select(Order::TABLE_NAME . '.*')
                ->whereNull(Order::TABLE_NAME . '.deleted_at')
                ->with('supplier')
                ->with('customer')
                ->with('orderStatus')
                ->where(Supplier::TABLE_NAME . '.acl_partner_users_id', '=', $user->id)
                ->find($id);
            $status = 404;
            if ($order->delivery_status == Order::STATUS_STARTED) {
                $status = 200;
                $params = $request->all();
                $order->commentary = isset($params['commentary']) ? $params['commentary'] : null;
                $order->delivery_status = Order::STATUS_ACCEPTED;
                $order->save();
                return response([
                    "status" => !empty($order) ? true : false,
                    "message" => !empty($order) ? "Orden Aceptada Correctamente" : "No se encontró la Orden",
                    "body" => $order,
                    "redirect" => false
                ], 200);
            } else {
                return response([
                    "status" => !empty($order) ? true : false,
                    "message" => !empty($order) ? "La Orden no puede ser aceptada" : "No se encontró la Orden",
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

    public function calculateDistanceCost(Request $request)
    {
        $response = response([
            "status"  => false,
            "message" => "Bad request",
            "body"    => null,
            "redirect" => false
        ], 400);

        $params = $request->all();

        if (isset($params['point_a'])
            && isset($params['point_b'])) {
            $response = response([
                "status" => true,
                "message" => "Ok",
                "body" => [
                    "cost" => 10.00,
                    "distance" => "... km"
                ],
                "redirect" => false
            ], 200);
        }

        return $response;
    }

    public function dashboardInfo(Request $request)
    {
        $user = Auth::user();
        if (!is_null($user)) {
            $params = $request->all();
            $orders = Order::join(Supplier::TABLE_NAME, Supplier::TABLE_NAME . '.id', '=',
                   Order::TABLE_NAME . '.bs_suppliers_id')
                ->select(Order::TABLE_NAME . '.*')
                ->whereNull(Order::TABLE_NAME . '.deleted_at')
                ->with('supplier')
                ->with('customer')
                ->with('orderStatus')
                ->where(Supplier::TABLE_NAME . '.acl_partner_users_id', '=', $user->id);
            if (isset($params['date']) && $params['date'] !== "") {
                $orders = $orders->where(Order::TABLE_NAME . '.created_at', 'like', '%' . $params['date'] . '%');
            }
            if (isset($params['orderBy']) && !is_null($params['orderBy'])) {
                $orders = $orders->orderBy($params['orderBy'], $params['orderDir']);
            } else {
                $orders = $orders->orderBy(Order::TABLE_NAME . '.created_at', 'DESC');
            }
            $incomes = $orders->sum('bs_orders.total');
            $customers = $orders->distinct('users_id')->count('users_id');
            $orders = $orders->get()->take(5);
            return response([
                "status" => !empty($orders) ? true : false,
                "clientes" => $customers,
                "total" => $incomes,
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
}

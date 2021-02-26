<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\MsOrderStatus;
use App\DeliveryUser;
use App\User;
use Kreait\Firebase\Database;
use App\Http\Controllers\Api\v1\NotificationController;

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
            $orders = Order::select(Order::TABLE_NAME . '.id',
                    Order::TABLE_NAME . '.users_id',
                    Order::TABLE_NAME . '.bs_suppliers_id',
                    Order::TABLE_NAME . '.status',
                    Order::TABLE_NAME . '.total',
                    Order::TABLE_NAME . '.created_at')
                ->whereNull(Order::TABLE_NAME . '.deleted_at')
                ->with('supplier')
                ->with('orderStatus')
                ->with('ranking')
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
            $deliveryCount = User::where('status', '1')
                ->where('type', '2')
                ->count();
            if ($deliveryCount > 0) {
                $params = $request->all();
                $params['users_id'] = $user->id;
                if (isset($params['details_info']) && is_array($params['details_info'])) {
                    $params['bs_suppliers_id'] = 0;
                    $countDemand = 0;
                    $demandAvailable = true;
                    foreach ($params['details_info'] as $key => $value) {
                        $params['bs_suppliers_id'] = $value['bs_suppliers_id'];
                        $countDemand++;
                    }
                    $supplier = Supplier::find($params['bs_suppliers_id']);
                    if (!is_null($supplier)) {
                        $freeDemand = $supplier->on_demand - $supplier->on_demand_now;
                        if ($freeDemand < $countDemand) {
                            $demandAvailable = false;
                        } else {
                            $supplier->on_demand_now = $supplier->on_demand_now + $countDemand;
                            $supplier->save();
                        }
                    }
                }
                if ($demandAvailable) {
                    $params['bs_delivery_id'] = $this->findDeliveryGuy($params);
                    $order = Order::create($params);
                    if (isset($params['address_info'])) {
                        $user->address_info = $params['address_info'];
                        $user->save();
                    }
                    // Create in firebase
                    $this->createOrderInFirebase($order, $user, "Gracias por usar Defreesa. Tu orden ha sido creada");
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
                        "message" => "Nuestro partner no puede atender tu pedido ahora mismo. Intenta nuevamente en unos minutos :)",
                        "body" => null,
                        "redirect" => true
                    ], 400);
                }
            } else {
                return response([
                    "status" => false,
                    "message" => "En este momento todos nuestros Defreevers se encuentran ocupados. Por favor, intenta en 5 minutos!",
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

    public function findDeliveryGuy($params = [])
    {
        $idDeliveryUser = 0;
        $deliveryUser = DeliveryUser::join(User::TABLE_NAME, User::TABLE_NAME . '.id', '=',
                DeliveryUser::TABLE_NAME . '.users_id')
            ->whereNull(DeliveryUser::TABLE_NAME . '.deleted_at')
            ->where(DeliveryUser::TABLE_NAME . '.active', '1')
            ->where(User::TABLE_NAME . '.status', '1')
            ->first();

        if (!is_null($deliveryUser)) {
            $idDeliveryUser = $deliveryUser->id;
        }
        return $idDeliveryUser;
    }

    public function createOrderInFirebase($order, $user = null, $message = "")
    {
        // create row in db
        $database = app('firebase.database');
        $database->getReference('orders/' . $order->users_id . '/')->push([
            'orderId' => $order->id,
            'users_id' => $order->users_id,
            'details_info' => $order->details_info,
            'status' => $order->status,
            'date' => $order->created_at,
            'supplier' => $order->bs_suppliers_id,
            'total' => $order->total,
            'bs_delivery_id' => $order->bs_delivery_id,
            'pickup_address_info' => $order->pickup_address_info,
            'address_info' => $order->address_info,
            'type_order' => $order->type_order,
            'detail_label_order' => $order->detail_label_order,
            'emisor_name' => $order->emisor_name,
            'emisor_phone' => $order->emisor_phone,
            'receptor_phone' => $order->receptor_phone,
            'commentary' => $order->commentary,
            'type_document' => $order->type_document,
            'document_number' => $order->document_number,
            'tips' => $order->tips,
            'delivery_amount' => $order->delivery_amount,
            'commentary_info' => $order->commentary_info,
            'flag_active' => $order->flag_active,
            'updated_at' => $order->updated_at,
            'deleted_at' => $order->deleted_at,
        ]);

        // send message
        NotificationController::sendFcmTo($user->firebase_token, "!! " . env('APP_NAME') . " !! - Órden nº: " . $order->id, $message);
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
                ->with('ranking')
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
            $deliveryUser = DeliveryUser::whereNull(DeliveryUser::TABLE_NAME . '.deleted_at')
                ->where(DeliveryUser::TABLE_NAME . '.users_id', $user->id)
                ->first();
            if (!is_null($deliveryUser)) {
                $order = Order::whereNotIn(Order::TABLE_NAME . '.status', [1, 5,6])
                    ->where(Order::TABLE_NAME . '.bs_delivery_id', $deliveryUser->id)
                    ->with('supplier')
                    ->with('customer')
                    ->with('orderStatus')
                    ->orderBy('created_at', 'DESC')
                    ->first();
                if (!is_null($order)) {
                    if ($order->status < 5) {
                        $msOrderStatus = MsOrderStatus::find($order->status + 1);
                        if (!is_null($msOrderStatus)) {
                            $msOrderStatus->name = "PASAR A: " . $msOrderStatus->name;
                        }
                        $order->order_next_status = $msOrderStatus;
                    }
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
                    "message" => "No se encontraron datos del usuario",
                    "body" => null,
                    "redirect" => true
                ], 400);
            }
        } else {
            return response([
                "status" => false,
                "message" => "Su sesión no se encuentra activa",
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
                NotificationController::sendFcmTo($user->firebase_token, "!! " . env('APP_NAME') . " !! - Órden nº: " . $order->id, "Tu orden esta siendo atendida... en breve estaremos en tu puerta.");
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
            if ($order->status > Order::STATUS_FINAL) {
                $order->status = $order->status + 1;
                $order->save();
                if (!is_null($order)) {
                    $msOrderStatus = MsOrderStatus::find($order->status + 1);
                    if (!is_null($msOrderStatus)) {
                        $msOrderStatus->name = "PASAR A: " . $msOrderStatus->name;
                    }
                    $order->order_next_status = $msOrderStatus;
                }
            } else {
                $order->flag_ranking_needed = Order::STATE_ACTIVE;
                $order->save();
            }
            return response([
                "status" => !empty($order) ? true : false,
                "message" => !empty($order) ? "Órden actualizada" : "Órden sin actualizar",
                "body" => $order,
                "redirect" => false
            ], 200);
        } else {
            return response([
                "status" => !empty($order) ? true : false,
                "message" => !empty($order) ? "Órden actualizada" : "Órden sin actualizar",
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
            if ($order->status == Order::STATUS_ACCEPTED) {
                $status = 200;
                $params = $request->all();
                $order->commentary = isset($params['commentary']) ? $params['commentary'] : null;
                $order->status = Order::STATUS_NOT_PROCEED;
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
            if ($order->status == Order::STATUS_ACCEPTED) {
                $status = 200;
                $params = $request->all();
                $order->commentary = isset($params['commentary']) ? $params['commentary'] : null;
                $order->status = Order::STATUS_PROCEED;
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
            "message" => "En este momento todos nuestros Defreevers se encuentran ocupados. Por favor, intenta en 5 minutos!",
            "body"    => null,
            "redirect" => false
        ], 400);

        $params = $request->all();

        if (isset($params['point_a'])
            && isset($params['point_b'])) {
            $users = User::where('status', '1')
                ->where('type', '2')
                ->count();
            if ($users > 0) {
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

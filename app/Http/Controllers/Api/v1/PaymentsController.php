<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;

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
    public function update(Request $request, $id)
    {
        //
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

    public function myFounds()
    {
        $user = Auth::user();
        if (!is_null($user)) {
            $payments = Payment::whereNull('deleted_at')->get();
            return response()->json(['balance' => 250.25, 
                                     'pending' => 24.58, 
                                     'list' => [
                                                    ['id' => 1, 
                                                    'created_at' => "2020-10-06T18:17:57.000000Z", 
                                                    'operation_supplier' => 'Gringo Perú',
                                                    'operation_customer' => 'ITALO BRAGAGNINI', 
                                                    'status_id'=> 5, 
                                                    'status_name'=> 'PAGADO', 
                                                    'amount'=> 5.83, 
                                                    'currency'=> 'PEN'
                                                    ],
                                                    ['id' => 2, 
                                                    'created_at' => "2020-10-06T18:19:57.000000Z", 
                                                    'operation_supplier' => 'Mc Donalds',
                                                    'operation_customer' => 'GABRIEL ARCE', 
                                                    'status_id'=> 2, 
                                                    'status_name'=> 'PENDIENTE', 
                                                    'amount'=> 6.24, 
                                                    'currency'=> 'PEN'
                                                    ],
                                                    ['id' => 3, 
                                                    'created_at' => "2020-10-06T19:19:57.000000Z", 
                                                    'operation_supplier' => 'KFC',
                                                    'operation_customer' => 'LADY ORTIZ', 
                                                    'status_id'=> 5, 
                                                    'status_name'=> 'PAGADO', 
                                                    'amount'=> 8.65, 
                                                    'currency'=> 'PEN'
                                                    ],
                                                    ['id' => 4, 
                                                    'created_at' => "2020-10-06T20:19:57.000000Z", 
                                                    'operation_supplier' => 'BEMBOS',
                                                    'operation_customer' => 'JOSE QUIROZ', 
                                                    'status_id'=> 6, 
                                                    'status_name'=> 'ANULADO', 
                                                    'amount'=> 5.98, 
                                                    'currency'=> 'PEN'
                                                    ],
                                                    ['id' => 5, 
                                                    'created_at' => "2020-10-06T20:30:57.000000Z", 
                                                    'operation_supplier' => 'FRIDAYS',
                                                    'operation_customer' => 'FRANCISCO SANCHEZ', 
                                                    'status_id'=> 5, 
                                                    'status_name'=> 'PAGADO', 
                                                    'amount'=> 5.74, 
                                                    'currency'=> 'PEN'
                                                    ]
                                                ]
                                    ]);
        } else {
            return response([
                "message" => "forbidden",
                "body" => null
            ], 403);
        }
    }
}

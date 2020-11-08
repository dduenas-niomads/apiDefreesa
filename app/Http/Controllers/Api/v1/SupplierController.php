<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Supplier;

class SupplierController extends Controller
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
            $suppliers = Supplier::whereNull('deleted_at');
            if (isset($params['category_id']) && (int)$params['category_id'] !== 0) {
                $suppliers = $suppliers->where('bs_categories_id', (int)$params['category_id']);
            }
            if (isset($params['key']) && $params['key'] !== "") {
                $suppliers = $suppliers->where('tags', "LIKE", "%" . $params['key'] . "%");
            }
            if (isset($params['search']) && !is_null($params['search'])) {
                $key = $params['search'];
                $suppliers = $suppliers->where(function($query) use ($key){
                    $query->where(Supplier::TABLE_NAME . '.name', 'LIKE', '%' . $key . '%');
                    $query->orWhere(Supplier::TABLE_NAME . '.description', 'LIKE', '%' . $key . '%');
                });
            }
            $suppliers = $suppliers->with('category')->paginate(env('ITEMS_PAGINATOR'));
            return response([
                "status" => !empty($suppliers) ? true : false,
                "message" => !empty($suppliers) ? "list of suppliers" : "suppliers not found",
                "body" => $suppliers,
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
            $supplier = new Supplier();
            $supplier = $supplier->create($params);
            return response([
                "status" => !empty($supplier) ? true : false,
                "message" => !empty($supplier) ? "Proveedor creado" : "No se pudo crear el proveedor",
                "body" => $supplier,
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
        $user = Auth::user();
        if (!is_null($user)) {
            $supplier = Supplier::find($id);
            if (!is_null($supplier)) {
                $params = $request->all();
                $supplier->fill($params);
                $supplier->save();
                return response([
                    "status" => !empty($supplier) ? true : false,
                    "message" => !empty($supplier) ? "Proveedor actualizado correctamente" : "Supplier not found",
                    "body" => $supplier,
                    "redirect" => false
                ], 200);
            } else {
                return response([
                    "status" => !empty($supplier) ? true : false,
                    "message" => !empty($supplier) ? "Proveedor actualizado correctamente" : "Supplier not found",
                    "body" => $supplier,
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
            $supplier = Supplier::find($id);
            if (!is_null($supplier)) {
                $params = $request->all();
                $supplier->flag_active = Supplier::STATE_INACTIVE;
                $supplier->deleted_at = date("Y-m-d H:i:s");
                $supplier->save();
                return response([
                    "status" => !empty($supplier) ? true : false,
                    "message" => !empty($supplier) ? "Proveedor eliminado correctamente" : "Supplier not found",
                    "body" => $supplier,
                    "redirect" => false
                ], 200);
            } else {
                return response([
                    "status" => !empty($supplier) ? true : false,
                    "message" => !empty($supplier) ? "Proveedor eliminado correctamente" : "Supplier not found",
                    "body" => $supplier,
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

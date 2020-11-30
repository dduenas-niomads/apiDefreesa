<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\MsProductCategory;

class ProductController extends Controller
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
            $products = Product::whereNull('deleted_at');
            if (isset($params['supplier_id']) && (int)$params['supplier_id'] !== 0) {
                $products = $products->where('bs_suppliers_id', (int)$params['supplier_id']);
            }
            $products = $products->with('category','supplier');
            if (isset($params['allItems']) && $params['allItems']) {
                $products = $products->orderBy('bs_ms_products_categories_id')->get();
                $category = null;
                $products_ = [ "data" => [] ];
                foreach ($products as $key => $value) {
                    if (!is_null($category)) {
                        if ($category->id != $value->category->id) {
                            $category = $value->category;
                            array_push($products_["data"], $category);
                        }
                    } else {
                        $category = $value->category;
                        array_push($products_["data"], $category);
                    }
                    array_push($products_["data"], $value);
                }
                $products = $products_;
            } else {
                $products = $products->paginate(env('ITEMS_PAGINATOR'));
            }
            return response([
                "status" => !empty($products) ? true : false,
                "message" => !empty($products) ? "list of products" : "products not found",
                "body" => $products,
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
            $products = new Product();
            $products = $products->create($params);
            return response([
                "status" => !empty($products) ? true : false,
                "message" => !empty($products) ? "Producto creado" : "No se pudo crear el Producto",
                "body" => $products,
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
            $products = Product::find($id);
            if (!is_null($products)) {
                $params = $request->all();
                $products->fill($params);
                $products->save();
                return response([
                    "status" => !empty($products) ? true : false,
                    "message" => !empty($products) ? "Producto actualizado correctamente" : "Product not found",
                    "body" => $products,
                    "redirect" => false
                ], 200);
            } else {
                return response([
                    "status" => !empty($products) ? true : false,
                    "message" => !empty($products) ? "Producto actualizado correctamente" : "Product not found",
                    "body" => $products,
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
            $products = Product::find($id);
            if (!is_null($products)) {
                $params = $request->all();
                $products->flag_active = Product::STATE_INACTIVE;
                $products->deleted_at = date("Y-m-d H:i:s");
                $products->save();
                return response([
                    "status" => !empty($products) ? true : false,
                    "message" => !empty($products) ? "Producto eliminado correctamente" : "Product not found",
                    "body" => $products,
                    "redirect" => false
                ], 200);
            } else {
                return response([
                    "status" => !empty($products) ? true : false,
                    "message" => !empty($products) ? "Producto eliminado correctamente" : "Product not found",
                    "body" => $products,
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

    public function getListMyProducts(Request $request)
    {
        $user = Auth::user();
        if (!is_null($user)) {
            $params = $request->all();
            $products = Product::join(Supplier::TABLE_NAME, Product::TABLE_NAME . '.bs_suppliers_id', '=',
                Supplier::TABLE_NAME . '.id')            
                ->whereNull(Product::TABLE_NAME . '.deleted_at');
            $products = $products->where(Supplier::TABLE_NAME . '.acl_partner_users_id', $user->id);
            
            $products = $products->with('category','supplier')
                                ->paginate(env('ITEMS_PAGINATOR'));
            return response([
                "status" => !empty($products) ? true : false,
                "message" => !empty($products) ? "list of products" : "products not found",
                "body" => $products,
                "redirect" => false
            ], 200);
        } else {
            return response([
                "message" => "forbidden",
                "body" => null
            ], 403);
        }
    }
}

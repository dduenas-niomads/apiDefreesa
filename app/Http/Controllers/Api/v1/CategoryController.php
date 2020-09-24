<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;

class CategoryController extends Controller
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
            $categories = Category::whereNull('deleted_at')
                ->where('flag_active', true);
            if (isset($params['search']) && !is_null($params['search'])) {
                $key = $params['search'];
                $categories = $categories->where(function($query) use ($key){
                    $query->where(Category::TABLE_NAME . '.name', 'LIKE', '%' . $key . '%');
                    $query->orWhere(Category::TABLE_NAME . '.description', 'LIKE', '%' . $key . '%');
                });
            }
            if (isset($params['orderBy']) && !is_null($params['orderBy'])) {
                $categories = $categories->orderBy($params['orderBy'], $params['orderDir']);
            }
            $categories = $categories->paginate(env('ITEMS_PAGINATOR'));
            return response([
                "status" => !empty($categories) ? true : false,
                "message" => !empty($categories) ? "list of categories" : "categories not found",
                "body" => $categories,
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
        return null;
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
            $category = Category::find($id);
            if (!is_null($category)) {
                $params = $request->all();
                $category->fill($params);
                $category->save();
                return response([
                    "status" => !empty($category) ? true : false,
                    "message" => !empty($category) ? "Categoría actualizada correctamente" : "Category not found",
                    "body" => $category,
                    "redirect" => false
                ], 200);
            } else {
                return response([
                    "status" => !empty($category) ? true : false,
                    "message" => !empty($category) ? "Categoría actualizada correctamente" : "Category not found",
                    "body" => $category,
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
    public function destroy($id)
    {
        return null;
    }
}

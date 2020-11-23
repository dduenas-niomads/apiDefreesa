<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Users
Route::prefix('/user')->group(function() {
    Route::post('/login', 'Api\v1\LoginController@login');
    Route::middleware('auth:api')->get('/list', 'Api\v1\UserController@index');
    Route::middleware('auth:api')->delete('/logout', 'Api\v1\LoginController@logout');
    Route::middleware('auth:api')->delete('/logout-all', 'Api\v1\LoginController@logoutAll');
    Route::middleware('auth:api')->get('/', 'Api\v1\UserController@show');
    Route::middleware('auth:api')->patch('/update', 'Api\v1\UserController@update');
});

// Licenses/Plans
Route::prefix('/licenses')->group(function() {
    Route::middleware('auth:api')->get('/', 'Api\v1\LicenseController@index');
    Route::middleware('auth:api')->get('/{id}', 'Api\v1\LicenseController@show');
    Route::middleware('auth:api')->get('/by-type/{type}', 'Api\v1\LicenseController@showByType');
});

// Categories
Route::prefix('/categories')->group(function() {
    Route::middleware('auth:api')->get('/simple', 'Api\v1\CategoryController@indexSimple');
    Route::middleware('auth:api')->get('/', 'Api\v1\CategoryController@index');
    Route::middleware('auth:api')->post('/', 'Api\v1\CategoryController@store');
    Route::middleware('auth:api')->patch('/{id}', 'Api\v1\CategoryController@update');
    Route::middleware('auth:api')->delete('/{id}', 'Api\v1\CategoryController@destroy');
});

// Suppliers
Route::prefix('/suppliers')->group(function() {
    Route::middleware('auth:api')->get('/simple', 'Api\v1\SupplierController@indexSimple');
    Route::middleware('auth:api')->get('/', 'Api\v1\SupplierController@index');
    Route::middleware('auth:api')->get('/my-suppliers', 'Api\v1\SupplierController@getListMySuppliers');
    Route::middleware('auth:api')->post('/', 'Api\v1\SupplierController@store');
    Route::middleware('auth:api')->patch('/{id}', 'Api\v1\SupplierController@update');
    Route::middleware('auth:api')->delete('/{id}', 'Api\v1\SupplierController@destroy');
});

// Products
Route::prefix('/products')->group(function() {
    Route::middleware('auth:api')->get('/', 'Api\v1\ProductController@index');
});

// Orders
Route::prefix('/orders')->group(function() {
    Route::middleware('auth:api')->get('/', 'Api\v1\OrderController@index');
    Route::middleware('auth:api')->get('/my-orders', 'Api\v1\OrderController@getListMyOrders');
    Route::middleware('auth:api')->post('/', 'Api\v1\OrderController@store');
    Route::middleware('auth:api')->get('/delivery-main-order', 'Api\v1\OrderController@showMainOrder');
    Route::middleware('auth:api')->get('/{id}', 'Api\v1\OrderController@show');
    Route::middleware('auth:api')->patch('/{id}', 'Api\v1\OrderController@update');
    Route::middleware('auth:api')->patch('/delivery-next-status/{id}', 'Api\v1\OrderController@deliveryNextStatus');
    Route::middleware('auth:api')->delete('/{id}', 'Api\v1\OrderController@destroy');
});

// DeliveryUsers
Route::prefix('/delivery-user')->group(function() {
    Route::middleware('auth:api')->get('/', 'Api\v1\DeliveryUserController@index');
    Route::middleware('auth:api')->post('/', 'Api\v1\DeliveryUserController@store');
    Route::middleware('auth:api')->get('/my-founds', 'Api\v1\DeliveryUserController@myFounds');
    Route::middleware('auth:api')->get('/{id}', 'Api\v1\DeliveryUserController@show');
    Route::middleware('auth:api')->patch('/{id}', 'Api\v1\DeliveryUserController@update');    
});

// Consumers
Route::prefix('/consumers')->group(function() {
    Route::middleware('auth:api')->get('/', 'Api\v1\ConsumerController@index'); 
});

// Partners
Route::prefix('/partners')->group(function() {
    Route::middleware('auth:api')->get('/', 'Api\v1\PartnerController@index'); 
});

// Payments
Route::prefix('/payments')->group(function() {
    Route::middleware('auth:api')->get('/my-founds', 'Api\v1\PaymentsController@myFounds');
    Route::middleware('auth:api')->get('/', 'Api\v1\PaymentsController@index');
    // Route::middleware('auth:api')->post('/', 'Api\v1\PaymentsController@store');
    Route::middleware('auth:api')->get('/{id}', 'Api\v1\PaymentsController@show');
    // Route::middleware('auth:api')->patch('/{id}', 'Api\v1\PaymentsController@update'); 
});
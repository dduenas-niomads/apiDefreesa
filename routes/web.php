<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return response([
        "message" => "defreesa api"
    ]);
});

Route::get('/login', function () {
    return response([
        "status" => false,
        "message" => "You don't have a valid sesion. Please, login",
        "body" => null,
        "redirect" => true
    ], 403);
})->name('login');

Route::post('/auth/register', 'Auth\RegisterController@create');

Route::post('/user/forgot-password', 'Auth\RegisterController@forgotPassword');

Route::get('/api/auth/signup/activate/{token}', 'Auth\RegisterController@signupActivate');

Route::get('/api/user/forgot-password/{token}', 'Auth\RegisterController@forgotPasswordActive');

// Auth::routes();

// Route::get('/home', 'HomeController@index')->name('home');

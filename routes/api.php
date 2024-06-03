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
$router->post('/register', 'AuthController@register');
$router->post('/login', 'AuthController@login');
$router->group(['prefix' => 'task'], function () use ($router) {
    $router->get('/', 'TaskController@index');
    $router->post('/create', 'TaskController@store');
    });
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

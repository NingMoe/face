<?php

use Illuminate\Http\Request;
use \Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get("face/index", "FaceController@index");
Route::get("face/create", "FaceController@create");
Route::post("face/savePhoto", "FaceController@savePhoto");
Route::get("face/search", "FaceController@search");
Route::get("face/get-face-image", "FaceController@getFaceImage");
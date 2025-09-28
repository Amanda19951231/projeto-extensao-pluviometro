<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PluviometrosController;

Route::post('/dados_pluviometros', [PluviometrosController::class, 'store_dados_pluviometros']);

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
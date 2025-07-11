<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\master\CitiesController;
use App\Http\Controllers\master\StatesController;
use App\Http\Controllers\master\SubdistrictController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Authentication routesd
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');

// Master data routes
// Master Customer
Route::post('/customer', [\App\Http\Controllers\master\CustomerController::class, 'createCustomer'])
    ->name('customer.create')
    ->middleware('auth:api');

<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\master\CountryController;
use App\Http\Controllers\master\CustomerController;
use App\Http\Controllers\master\AirportController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');


// Master data routes

// Master Customer
Route::get('/getCustomer', [CustomerController::class, 'getCustomer'])->middleware('auth:api');
Route::get('/getCustomerById/{id}', [CustomerController::class, 'getCustomerById'])->middleware('auth:api');
Route::post('/createCustomer', [CustomerController::class, 'createCustomer'])->middleware('auth:api');
Route::put('/deactivateCustomerById', [CustomerController::class, 'deactiveCustomer'])->middleware('auth:api');
Route::put('/activateCustomerById', [CustomerController::class, 'activateCustomer'])->middleware('auth:api');
Route::post('/addDetailCustomer', [CustomerController::class, 'addDetailCustomer'])->middleware('auth:api');
route::put('/updateDetailCustomer', [CustomerController::class, 'updateDetailCustomer'])->middleware('auth:api');

// Master Country
Route::post('/createCountry', [CountryController::class, 'createCountry'])->middleware('auth:api');
Route::get('/getCountry', [CountryController::class, 'getCountry'])->middleware('auth:api');
Route::put('/deactivateCountryById', [CountryController::class, 'deactivateCountry'])->middleware('auth:api');
Route::put('/activateCountryById', [CountryController::class, 'activateCountry'])->middleware('auth:api');
Route::put('/updateCountryById', [CountryController::class, 'updateCountry'])->middleware('auth:api');

// Master Airport
Route::post('/createAirport', [AirportController::class, 'createAirport'])->middleware('auth:api');
Route::get('/getAirport', [AirportController::class, 'getAirport'])->middleware('auth:api');
Route::put('/deactivateAirportById', [AirportController::class, 'deactivateAirport'])->middleware('auth:api');
Route::put('/activateAirportById', [AirportController::class, 'activateAirport'])->middleware('auth:api');
Route::put('/updateAirportById', [AirportController::class, 'updateAirport'])->middleware('auth:api');



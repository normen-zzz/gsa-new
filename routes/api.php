<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\flow\JobController;
use App\Http\Controllers\master\MenuController;
use App\Http\Controllers\master\RoleController;
use App\Http\Controllers\master\UsersController;
use App\Http\Controllers\master\AirportController;
use App\Http\Controllers\master\CountryController;
use App\Http\Controllers\master\CustomerController;
use App\Http\Controllers\master\DivisionController;
use App\Http\Controllers\master\PositionController;
use App\Http\Controllers\master\PermissionController;
use App\Http\Controllers\flow\ShippingInstructionController;


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
Route::put('/updateDetailCustomer', [CustomerController::class, 'updateDetailCustomer'])->middleware('auth:api');
Route::put('/updateCustomer', [CustomerController::class, 'updateCustomer'])->middleware('auth:api');

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

//Master Position
Route::get('/getPositions', [PositionController::class, 'getPositions'])->middleware('auth:api');
Route::get('/getPositionById/{id}', [PositionController::class, 'getPositionById'])->middleware('auth:api');
Route::post('/createPosition', [PositionController::class, 'createPosition'])->middleware('auth:api');
Route::put('/updatePosition', [PositionController::class, 'updatePosition'])->middleware('auth:api');

//Master Division
Route::get('/getDivisions', [DivisionController::class, 'getDivisions'])->middleware('auth:api');
Route::get('/getDivisionById/{id}', [DivisionController::class, 'getDivisionById'])->middleware('auth:api');
Route::post('/createDivision', [DivisionController::class, 'createDivision'])->middleware('auth:api');
Route::put('/updateDivision', [DivisionController::class, 'updateDivision'])->middleware('auth:api');

//Master Role
Route::get('/getRoles', [RoleController::class, 'getRoles'])->middleware('auth:api');
Route::get('/getRoleById/{id}', [RoleController::class, 'getRoleById'])->middleware('auth:api');
Route::post('/createRole', [RoleController::class, 'createRole'])->middleware('auth:api');
Route::put('/updateRole', [RoleController::class, 'updateRole'])->middleware('auth:api');

//Master Menu
Route::get('/getListMenu', [MenuController::class, 'getListMenu'])->middleware('auth:api');
Route::get('/getListMenuById/{id}', [MenuController::class, 'getListMenuById'])->middleware('auth:api');
Route::post('/createListMenu', [MenuController::class, 'createListMenu'])->middleware('auth:api');
Route::put('/updateListMenu', [MenuController::class, 'updateListMenu'])->middleware('auth:api');

//Master Menu User
Route::get('/getMenuUser', [MenuController::class, 'getMenuUser'])->middleware('auth:api');
Route::get('/getMenuUserById/{id}', [MenuController::class, 'getMenuUserById'])->middleware('auth:api');
Route::post('/createMenuUser', [MenuController::class, 'createMenuUser'])->middleware('auth:api');
Route::put('/updateMenuUser', [MenuController::class, 'updateMenuUser'])->middleware('auth:api');

//master permission
Route::get('/getPermissions', [PermissionController::class, 'getPermissions'])->middleware('auth:api');
Route::get('/getPermissionById/{id}', [PermissionController::class, 'getPermissionById'])->middleware('auth:api');
Route::post('/createPermission', [PermissionController::class, 'createPermission'])->middleware('auth:api');
Route::put('/updatePermission', [PermissionController::class, 'updatePermission'])->middleware('auth:api');




// Flow routes

//shipping instruction
Route::get('/getShippingInstruction', [ShippingInstructionController::class, 'getShippingInstructions'])->middleware('auth:api');
Route::post('/createShippingInstruction', [ShippingInstructionController::class, 'createShippingInstruction'])->middleware('auth:api');
Route::put('/updateShippingInstruction', [ShippingInstructionController::class, 'updateShippingInstruction'])->middleware('auth:api');
Route::put('/deleteShippingInstruction', [ShippingInstructionController::class, 'deleteShippingInstruction'])->middleware('auth:api');
Route::put('/receiveShippingInstruction', [ShippingInstructionController::class, 'receiveShippingInstruction'])->middleware('auth:api');
Route::put('/rejectShippingInstruction', [ShippingInstructionController::class, 'rejectShippingInstruction'])->middleware('auth:api');
Route::get('/getShippingInstructionById', [ShippingInstructionController::class, 'getShippingInstructionById'])->middleware('auth:api');


//Job
Route::get('/getJob', [JobController::class, 'getJob'])->middleware('auth:api');
Route::put('/updateJob', [JobController::class, 'updateJob'])->middleware('auth:api');
Route::get('/getJobById', [JobController::class, 'getJobById'])->middleware('auth:api');
Route::put('/executeJob', [JobController::class, 'executeJob'])->middleware('auth:api');





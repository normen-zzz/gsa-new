<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\flow\JobController;
use App\Http\Controllers\flow\salesorder\SalesorderController;
use App\Http\Controllers\master\CostController;
use App\Http\Controllers\master\MenuController;
use App\Http\Controllers\master\RoleController;
use App\Http\Controllers\master\RuteController;
use App\Http\Controllers\master\UsersController;
use App\Http\Controllers\master\AirlineController;
use App\Http\Controllers\master\AirportController;
use App\Http\Controllers\master\CountryController;
use App\Http\Controllers\master\SellingController;
use App\Http\Controllers\master\CustomerController;
use App\Http\Controllers\master\DivisionController;
use App\Http\Controllers\master\PositionController;
use App\Http\Controllers\master\TypecostController;
use App\Http\Controllers\master\PermissionController;
use App\Http\Controllers\master\TypesellingController;
use App\Http\Controllers\master\WeightbracketController;
use App\Http\Controllers\flow\ShippingInstructionController;
use App\Http\Controllers\master\FlowApprovalController;






// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
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

//master airlines
Route::get('/getAirlines', [AirlineController::class, 'getAirlines'])->middleware('auth:api');
Route::get('/getAirlineById', [AirlineController::class, 'getAirlineById'])->middleware('auth:api');
Route::post('/createAirline', [AirlineController::class, 'createAirline'])->middleware('auth:api');
Route::put('/updateAirline', [AirlineController::class, 'updateAirline'])->middleware('auth:api');

//master type selling
Route::get('/getTypeselling', [TypesellingController::class, 'getTypeselling'])->middleware('auth:api');
Route::get('/getTypesellingById', [TypesellingController::class, 'getTypesellingById'])->middleware('auth:api');
Route::post('/createTypeselling', [TypesellingController::class, 'createTypeselling'])->middleware('auth:api');
Route::put('/updateTypeselling', [TypesellingController::class, 'updateTypeselling'])->middleware('auth:api');
Route::put('/deleteTypeselling', [TypesellingController::class, 'deleteTypeselling'])->middleware('auth:api');
Route::put('/restoreTypeselling', [TypesellingController::class, 'restoreTypeselling'])->middleware('auth:api');

//master type cost
Route::get('/getTypecost', [TypecostController::class, 'getTypecost'])->middleware('auth:api');
Route::get('/getTypecostById', [TypecostController::class, 'getTypecostById'])->middleware('auth:api');
Route::post('/createTypecost', [TypecostController::class, 'createTypecost'])->middleware('auth:api');
Route::put('/updateTypecost', [TypecostController::class, 'updateTypecost'])->middleware('auth:api');
// Route::put('/deleteTypecost', [TypecostController::class, 'deleteTypecost'])->middleware('auth:api');
Route::put('/deleteTypecost', [TypecostController::class, 'deleteTypecost'])->middleware('auth:api');
Route::put('/restoreTypecost', [TypecostController::class, 'restoreTypecost'])->middleware('auth:api');

//master cost
Route::get('/getCost', [CostController::class, 'getCost'])->middleware('auth:api');
Route::get('/getCostById', [CostController::class, 'getCostById'])->middleware('auth:api');
Route::post('/createCost', [CostController::class, 'createCost'])->middleware('auth:api');
Route::put('/updateCost', [CostController::class, 'updateCost'])->middleware('auth:api');
Route::put('/deleteCost', [CostController::class, 'deleteCost'])->middleware('auth:api');
Route::put('/restoreCost', [CostController::class, 'restoreCost'])->middleware('auth:api');

//master selling
Route::get('/getSelling', [SellingController::class, 'getSelling'])->middleware('auth:api');
Route::post('/createSelling', [SellingController::class, 'createSelling'])->middleware('auth:api');
Route::put('/updateSelling', [SellingController::class, 'updateSelling'])->middleware('auth:api');
Route::put('/deleteSelling', [SellingController::class, 'deleteSelling'])->middleware('auth:api');
Route::put('/restoreSelling', [SellingController::class, 'restoreSelling'])->middleware('auth:api');

//master weight bracket cost
Route::get('/getWeightBracketCost', [WeightbracketController::class, 'getWeightBracketCost'])->middleware('auth:api');
Route::post('/createWeightBracketCost', [WeightbracketController::class, 'createWeightBracketCost'])->middleware('auth:api');
Route::put('/updateWeightBracketCost', [WeightbracketController::class, 'updateWeightBracketCost'])->middleware('auth:api');
Route::put('/deleteWeightBracketCost', [WeightbracketController::class, 'deleteWeightBracketCost'])->middleware('auth:api');
Route::put('/restoreWeightBracketCost', [WeightbracketController::class, 'restoreWeightBracketCost'])->middleware('auth:api');

//master weight bracket selling
Route::get('/getWeightBracketSelling', [WeightbracketController::class, 'getWeightBracketsSelling'])->middleware('auth:api');
Route::post('/createWeightBracketSelling', [WeightbracketController::class, 'createWeightBracketSelling'])->middleware('auth:api');
Route::put('/updateWeightBracketSelling', [WeightbracketController::class, 'updateWeightBracketSelling'])->middleware('auth:api');
Route::put('/deleteWeightBracketSelling', [WeightbracketController::class, 'deleteWeightBracketSelling'])->middleware('auth:api');
Route::put('/restoreWeightBracketSelling', [WeightbracketController::class, 'restoreWeightBracketSelling'])->middleware('auth:api');

// master routes
Route::get('/getRoutes', [RuteController::class, 'getRoutes'])->middleware('auth:api');
Route::get('/getRouteById/{id}', [RuteController::class, 'getRouteById'])->middleware('auth:api');
Route::post('/createRoute', [RuteController::class, 'createRoute'])->middleware('auth:api');
Route::put('/updateRoute', [RuteController::class, 'updateRoute'])->middleware('auth:api');
Route::put('/deleteRoute', [RuteController::class, 'deleteRoute'])->middleware('auth:api');
Route::put('/restoreRoute', [RuteController::class, 'restoreRoute'])->middleware('auth:api');


Route::get('/getFlowApprovalSalesOrder', [FlowApprovalController::class, 'getFlowApprovalSalesOrder'])->middleware('auth:api');
Route::post('/createFlowApprovalSalesOrder', [FlowApprovalController::class, 'createFlowApprovalSalesOrder'])->middleware('auth:api');
Route::put('/updateFlowApprovalSalesOrder', [FlowApprovalController::class, 'updateFlowApprovalSalesOrder'])->middleware('auth:api');
Route::put('/deleteFlowApprovalSalesOrder', [FlowApprovalController::class, 'deleteFlowApprovalSalesOrder'])->middleware('auth:api');





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

Route::get('/getExecuteJob', [JobController::class, 'getExecuteJob'])->middleware('auth:api');
Route::get('/getExecuteJobById', [JobController::class, 'getExecuteJobById'])->middleware('auth:api');
Route::put('/executeJob', [JobController::class, 'executeJob'])->middleware('auth:api');
route::put('/updateExecuteJob', [JobController::class, 'updateExecuteJob'])->middleware('auth:api');
Route::put('/finishExecuteJob', [JobController::class, 'finishExecuteJob'])->middleware('auth:api');

//hawb
Route::get('/getHawb', [JobController::class, 'getHawb'])->middleware('auth:api');
Route::get('/getHawbById', [JobController::class, 'getHawbById'])->middleware('auth:api');
Route::post('/createHawb', [JobController::class, 'createHawb'])->middleware('auth:api');
Route::put('/updateHawb', [JobController::class, 'updateHawb'])->middleware('auth:api');
Route::put('/deleteHawb', [JobController::class, 'deleteHawb'])->middleware('auth:api');
Route::put('/deleteDimensionhawb', [JobController::class, 'deleteDimensionHawb'])->middleware('auth:api');
Route::post('/addDimensionHawb', [JobController::class, 'addDimensionHawb'])->middleware('auth:api');


//salesorder
Route::post('/createSalesorder', [SalesorderController::class, 'createSalesorder'])->middleware('auth:api');
Route::get('/getSalesorder', [SalesorderController::class, 'getSalesorder'])->middleware('auth:api');

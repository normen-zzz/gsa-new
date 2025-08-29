<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\flow\JobController;
use App\Http\Controllers\master\CostController;
use App\Http\Controllers\master\MenuController;
use App\Http\Controllers\master\RoleController;
use App\Http\Controllers\master\RuteController;
use App\Http\Controllers\master\UsersController;
use App\Http\Controllers\master\VendorController;
use App\Http\Controllers\master\AirlineController;
use App\Http\Controllers\master\AirportController;
use App\Http\Controllers\master\CountryController;
use App\Http\Controllers\master\SellingController;
use App\Http\Controllers\master\CustomerController;
use App\Http\Controllers\master\DivisionController;
use App\Http\Controllers\master\PositionController;
use App\Http\Controllers\master\TypecostController;
use App\Http\Controllers\master\PermissionController;
use App\Http\Controllers\master\DatacompanyController;
use App\Http\Controllers\master\TypesellingController;
use App\Http\Controllers\master\FlowApprovalController;
use App\Http\Controllers\flow\invoice\InvoiceController;
use App\Http\Controllers\master\WeightbracketController;
use App\Http\Controllers\flow\jobsheet\JobsheetController;
use App\Http\Controllers\flow\ShippingInstructionController;
use App\Http\Controllers\flow\salesorder\SalesorderController;
use App\Http\Controllers\master\OtherchargesinvoiceController;






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


//master vendors
Route::get('/getVendor', [VendorController::class, 'getVendor'])->middleware('auth:api');
Route::get('/getVendorById/{id}', [VendorController::class, 'getVendorById'])->middleware('auth:api');
Route::post('/createVendor', [VendorController::class, 'createVendor'])->middleware('auth:api');
Route::put('/deactivateVendorById', [VendorController::class, 'deactiveVendor'])->middleware('auth:api');
Route::put('/activateVendorById', [VendorController::class, 'activateVendor'])->middleware('auth:api');
Route::put('/updateDetailVendor', [VendorController::class, 'updateDetailVendor'])->middleware('auth:api');
Route::put('/updateVendor', [VendorController::class, 'updateVendor'])->middleware('auth:api');


//Master Data Company
Route::get('/getDataCompany', [DatacompanyController::class, 'getDatacompany'])->middleware('auth:api');
Route::get('/getDataCompanyById', [DatacompanyController::class, 'getDatacompanyById'])->middleware('auth:api');
Route::post('/createDataCompany', [DatacompanyController::class, 'createDatacompany'])->middleware('auth:api');
Route::put('/updateDataCompany', [DatacompanyController::class, 'updateDatacompany'])->middleware('auth:api');
Route::put('/deleteDataCompany', [DatacompanyController::class, 'deleteDatacompany'])->middleware('auth:api');
Route::put('/activateDataCompany', [DatacompanyController::class, 'activateDatacompany'])->middleware('auth:api');

//master other charge invoice
Route::get('/getOtherchargesinvoice', [OtherchargesinvoiceController::class, 'getOtherchargesinvoice'])->middleware('auth:api');
Route::get('/getOtherchargesinvoiceById', [OtherchargesinvoiceController::class, 'getOtherchargesinvoiceById'])->middleware('auth:api');
Route::post('/createOtherchargesinvoice', [OtherchargesinvoiceController::class, 'createOtherchargesinvoice'])->middleware('auth:api');
Route::put('/updateOtherchargesinvoice', [OtherchargesinvoiceController::class, 'updateOtherchargesinvoice'])->middleware('auth:api');
Route::put('/deleteOtherchargesinvoice', [OtherchargesinvoiceController::class, 'deleteOtherchargesinvoice'])->middleware('auth:api');
Route::put('/activateOtherchargesinvoice', [OtherchargesinvoiceController::class, 'activateOtherchargesinvoice'])->middleware('auth:api');


//salesorder flow approval
Route::get('/getFlowApprovalSalesOrder', [FlowApprovalController::class, 'getFlowApprovalSalesOrder'])->middleware('auth:api');
Route::post('/createFlowApprovalSalesOrder', [FlowApprovalController::class, 'createFlowApprovalSalesOrder'])->middleware('auth:api');
Route::put('/updateFlowApprovalSalesOrder', [FlowApprovalController::class, 'updateFlowApprovalSalesOrder'])->middleware('auth:api');
Route::put('/deleteFlowApprovalSalesOrder', [FlowApprovalController::class, 'deleteFlowApprovalSalesOrder'])->middleware('auth:api');
Route::put('/activateFlowApprovalSalesOrder', [FlowApprovalController::class, 'activateFlowApprovalSalesOrder'])->middleware('auth:api');


//jobsheet flow approval
Route::get('/getFlowApprovalJobsheet', [FlowApprovalController::class, 'getFlowApprovalJobsheet'])->middleware('auth:api');
Route::post('/createFlowApprovalJobsheet', [FlowApprovalController::class, 'createFlowApprovalJobsheet'])->middleware('auth:api');
Route::put('/updateFlowApprovalJobsheet', [FlowApprovalController::class, 'updateFlowApprovalJobsheet'])->middleware('auth:api');
Route::put('/deleteFlowApprovalJobsheet', [FlowApprovalController::class, 'deleteFlowApprovalJobsheet'])->middleware('auth:api');
Route::put('/activateFlowApprovalJobsheet', [FlowApprovalController::class, 'activateFlowApprovalJobsheet'])->middleware('auth:api');

//invoice flow approval
Route::get('/getFlowApprovalInvoice', [FlowApprovalController::class, 'getFlowApprovalInvoice'])->middleware('auth:api');
Route::post('/createFlowApprovalInvoice', [FlowApprovalController::class, 'createFlowApprovalInvoice'])->middleware('auth:api');
Route::put('/updateFlowApprovalInvoice', [FlowApprovalController::class, 'updateFlowApprovalInvoice'])->middleware('auth:api');
Route::put('/deleteFlowApprovalInvoice', [FlowApprovalController::class, 'deleteFlowApprovalInvoice'])->middleware('auth:api');
Route::put('/activateFlowApprovalInvoice', [FlowApprovalController::class, 'activateFlowApprovalInvoice'])->middleware('auth:api');





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
Route::get('/getSalesorderById', [SalesorderController::class, 'getSalesorderById'])->middleware('auth:api');
Route::put('/deleteAttachmentSalesorder', [SalesorderController::class, 'deleteAttachmentSalesorder'])->middleware('auth:api');
Route::put('/updateSalesorder', [SalesorderController::class, 'updateSalesorder'])->middleware('auth:api');
Route::put('/deleteSalesorder', [SalesorderController::class, 'deleteSalesorder'])->middleware('auth:api');
Route::put('/activateSalesorder', [SalesorderController::class, 'activateSalesorder'])->middleware('auth:api');
Route::put('/actionSalesorder', [SalesorderController::class, 'actionSalesorder'])->middleware('auth:api');

//jobsheet
Route::post('/createJobsheet', [JobsheetController::class, 'createJobsheet'])->middleware('auth:api');
Route::get('/getJobsheet', [JobsheetController::class, 'getJobsheet'])->middleware('auth:api');
Route::get('/getJobsheetById', [JobsheetController::class, 'getJobsheetById'])->middleware('auth:api');
Route::put('/deleteAttachmentJobsheet', [JobsheetController::class, 'deleteAttachmentJobsheet'])->middleware('auth:api');
Route::put('/updateJobsheet', [JobsheetController::class, 'updateJobsheet'])->middleware('auth:api');
Route::put('/deleteJobsheet', [JobsheetController::class, 'deleteJobsheet'])->middleware('auth:api');
Route::put('/activateJobsheet', [JobsheetController::class, 'activateJobsheet'])->middleware('auth:api');
Route::put('/actionJobsheet', [JobsheetController::class, 'actionJobsheet'])->middleware('auth:api');
Route::get('/getUninvoicedJobsheet', [JobsheetController::class, 'getUninvoicedJobsheet'])->middleware('auth:api');

//invoice
Route::post('/createInvoice', [InvoiceController::class, 'createInvoice'])->middleware('auth:api');
Route::get('/getInvoice', [InvoiceController::class, 'getInvoice'])->middleware('auth:api');
Route::get('/getInvoiceById', [InvoiceController::class, 'getInvoiceById'])->middleware('auth:api');
Route::put('/updateInvoice', [InvoiceController::class, 'updateInvoice'])->middleware('auth:api');
Route::put('/deleteInvoice', [InvoiceController::class, 'deleteInvoice'])->middleware('auth:api');
Route::put('/activateInvoice', [InvoiceController::class, 'activateInvoice'])->middleware('auth:api');
Route::put('/actionInvoice', [InvoiceController::class, 'actionInvoice'])->middleware('auth:api');
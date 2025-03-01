<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Authcontroller;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UnitMeasurementController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProductCardController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\BasketController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FavoritesController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PackerController;
use App\Http\Controllers\PriceRequestController;
use App\Http\Controllers\SubCardController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\FinancialElementController;
use App\Http\Controllers\PriceOfferController;  // HEAD line
use App\Http\Controllers\ProductController;      // HEAD line
use App\Http\Controllers\ProfileController;      // HEAD line
use App\Http\Controllers\ReferenceController;    // HEAD line
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\WarehousesController;
use App\Models\FinancialOrder;

/**
 * Chat Routes
 */
Route::post('/send-message', [ChatController::class, 'sendMessage']);
Route::get('messages', [ChatController::class, 'getMessages']);

/**
 * Courier Routes
 */
Route::middleware(['auth:sanctum', 'role:courier'])->group(function () {
   Route::get('getCourierOrders', [CourierController::class, 'getCourierOrders']);
   Route::post('storeCourierDocument', [CourierController::class, 'storeCourierDocument']);
   Route::get('courier/orders/{orderId}', [CourierController::class, 'getCourierOrderDetails']);
   Route::post('/courier/orders/{orderId}/deliver', [CourierController::class, 'submitCourierDelivery']);
});

   /**
    * Auth
    */
   Route::post('/login', [AuthController::class, 'login']);
   Route::post('/register', [AuthController::class, 'register']);

   /**
    * Courier Users
    */
   Route::get('getCourierUsers',[CourierController::class,'getCourierUsers']);
   Route::get('/packer_document/{id}', [PackerController::class, 'get_packer_document']);

   /**
    * Admin Offer Requests
    */
   Route::post('/admin/offer-requests', [AdminController::class, 'createOfferRequest']);
   Route::get('/admin/offer-requests', [AdminController::class, 'getOfferRequests']);
   Route::get('/admin/offer-requests/{id}', [AdminController::class, 'getOfferRequest']);

   /**
    * Authenticated (Sanctum) Routes
    */
   Route::middleware('auth:sanctum')->group(function () {
   Route::post('/upload-photo', [ProfileController::class, 'uploadPhoto']);
   Route::get('/profile', [ProfileController::class, 'getProfile']);
   Route::put('/profile', [ProfileController::class, 'updateProfile']);

   // toggling notifications
   Route::post('/toggle-notifications', [UserController::class, 'toggleNotifications']); 
   Route::get('/user', [UserController::class, 'getUser']);
   Route::post('/logout', [AuthController::class,'logout']);
});

   /**
    * Admin Routes
    */
   Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
   /**
    * Product Cards
    */
   Route::post('product_card_create', [ProductCardController::class, 'store']);
   Route::get('/product_cards', [ProductCardController::class, 'getCardProducts']);
   Route::put('product_cards/{id}', [ProductCardController::class, 'updateProductCard']);
   Route::delete('/product_cards/{id}', [ProductCardController::class, 'destroy']);

   /**
    * Admin Warehouses
    */
   Route::post('/admin_warehouses', [AdminController::class, 'createWarehouse']);
   Route::post('/receivingBulkStore', [AdminController::class, 'receivingBulkStore']);

   // Additional Admin
   Route::post('/admin/product-groups', [AdminController::class, 'addProductToWarehouse']);
   Route::get('/warehouses/{id}/products', [AdminController::class, 'getProductsByWarehouse']);

   // Inventory / Warehouse
   Route::get('/inventory/admin-warehouse', [WarehousesController::class, 'getRemainingQuantities']);
   Route::post('/inventory/transfer', [WarehousesController::class, 'transferToGeneralWarehouse']);

   /**
    * Subcards
    */
   Route::post('/product_subcards', [SubCardController::class, 'store']);
   Route::get('/product_subcards', [SubCardController::class, 'getSubCards']);
   Route::put('/product_subcards/{id}', [SubCardController::class, 'update']);
   Route::delete('/product_subcards/{id}', [SubCardController::class, 'destroy']);

   /**
    * Providers
    */
   Route::get('providers',[AdminController::class,'getProviders']);
   Route::post('create_providers', [AdminController::class,'storeProvider']);
   // Route::put('update_providers', [AdminController::class,'updateProvider']);
   // Route::delete('delete_providers', [AdminController::class,'deleteProvider']);

   /**
    * Users & Roles
    */
   Route::put('/users/{user}/assign-roles', [UserController::class, 'assignRoles']);
   Route::delete('/users/{user}/remove-role', [UserController::class, 'removeRole']);
   Route::post('/create-account', [AuthController::class, 'createAccount']);

   Route::get('/users', [UserController::class, 'index']);
   Route::post('/users', [UserController::class, 'storeUser']);
   Route::put('/users/{user}', [UserController::class, 'update']);
   Route::delete('/users/{id}', [UserController::class, 'deleteUser']);

   /**
    * Organizations
    */
   Route::get('/organizations', [OrganizationController::class, 'index']);
   Route::post('/organizations', [OrganizationController::class, 'store']);
   Route::put('/organizations/{organization}', [OrganizationController::class, 'update']);
   Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy']);

   /**
    * Unit Measurements
    */
   Route::get('/unit-measurements', [UnitMeasurementController::class, 'index']);
   Route::post('unit-measurements', [UnitMeasurementController::class, 'store']);
   Route::put('/unit-measurements/{id}', [UnitMeasurementController::class, 'update']);
   Route::delete('/unit-measurements/{id}', [UnitMeasurementController::class, 'destroy']);

   /**
    * Sales
    */
   Route::get('getSalesWithDetails', [SalesController::class, 'getSalesWithDetails']);
   // Route::post('sales', [SalesController::class, 'store']);
   Route::post('bulk_sales', [SalesController::class, 'bulkStore']);
   Route::put('/sales/{id}', [SalesController::class, 'update']);
   Route::delete('/sales/{id}', [SalesController::class, 'destroy']);

   /**
    * Price Offer
    */
   Route::post('bulkPriceOffers', [PriceOfferController::class, 'bulkPriceOffers']);

   /**
    * Admin Inventory
    */
   Route::get('operations-history', [AdminController::class, 'fetchOperationsHistory']);
   Route::put('operations/{id}/{type}', [AdminController::class, 'updateOperation']);
   Route::delete('operations/{id}/{type}', [AdminController::class, 'deleteOperation']);
   Route::get('operations-card-history', [AdminController::class, 'fetchOperationsHistory']);

   /**
    * Product Histories
    */
   Route::get('/products/allHistories', [ProductController::class, 'fetchAllHistories']);
   Route::patch('/products/{type}/{id}', [ProductController::class, 'update']);
   Route::delete('/products/{type}/{id}', [ProductController::class, 'destroy']);

   /**
    * References
    */
   Route::get('/references/{type}', [ReferenceController::class, 'fetch']);
   Route::patch('/references/{type}/{id}', [ReferenceController::class, 'update']);
   Route::delete('/references/{type}/{id}', [ReferenceController::class, 'destroy']);

   /**
    * Storage
    */
   Route::get('getStorageUsers',[StorageController::class,'getStorageUsers']);
   Route::post('sendToGeneralWarehouse',[StorageController::class,'sendToGeneralWarehouse']);
   Route::get('getInventory',[StorageController::class,'getInventory']);
   Route::post('bulkStoreInventory',[StorageController::class,'bulkStoreInventory']);
   Route::post('bulkWriteOff',[StorageController::class,'bulkWriteOff']);

   /**
    * Expenses
    */
   Route::post('create_expense', [ExpenseController::class, 'store']);

   /**
    * Addresses
    */
   Route::post('storeAdress/{userId}', [AddressController::class, 'storeAdress']);
   Route::get('getClientAdresses',[AddressController::class, 'getClientAddresses']);
   Route::get('getStorageUserAddresses',[AddressController::class, 'getStorageUserAddresses']);

   Route::get('fetch_data_of_price_offer',[PriceOfferController::class,'fetch_data_of_price_offer'])->name('fetch_data_of_price_offer');

    /**
     * Reports
     */
   Route::get('/reports/cash-flow', [ReportsController::class, 'cashFlowReport']);
   Route::get('/reports/warehouse', [ReportsController::class, 'warehouseReport']);
   Route::get('/reports/debts', [ReportsController::class, 'debtsReport']);
   Route::get('/reports/sales', [ReportsController::class, 'salesReport']);
});

/**
 * Client & Admin (shared) Routes
 */
Route::middleware(['auth:sanctum', 'role:client,admin'])->group(function () {
    Route::get('getClientAdresses',[AddressController::class, 'getClientAddresses']);
});

/**
 * Client Routes
 */
Route::middleware(['auth:sanctum', 'role:client'])->group(function () {
    Route::get('/product-data', [ClientController::class, 'getAllProductData']);
    Route::get('getUserPriceOffers', [PriceOfferController::class, 'getUserPriceOffers']); // HEAD line
    Route::get('getSalesClientPage', [SalesController::class, 'getSalesWithDetails']);
    Route::get('/product_subcards_for_clientpage', [SubCardController::class, 'getSubCards']);
    Route::get('/product_cards_for_clientpage', [ProductCardController::class, 'getCardProducts']);
    Route::post('/confirm-courier-document', [CourierController::class, 'confirmCourierDocument']);
    Route::get('/client-order-items', [ClientController::class, 'getClientOrderItems']);

    // Basket
    Route::get('basket', [BasketController::class, 'index']);
    Route::post('basket/add', [BasketController::class, 'add']);
    Route::post('basket/remove', [BasketController::class, 'remove']);
    Route::post('basket/clear', [BasketController::class, 'clear']);
    Route::post('basket/place-order', [BasketController::class, 'placeOrder']);

    // Favorites
    Route::get('getFavorites', [FavoritesController::class, 'getFavorites']);
    Route::post('addToFavorites', [FavoritesController::class, 'addToFavorites']);
    Route::post('removeFromFavorites', [FavoritesController::class, 'removeFromFavorites']);
});

/**
 * Cashbox & Admin
 */
Route::middleware(['auth:sanctum', 'role:cashbox,admin'])->group(function () {
    Route::get('/financial-elements', [FinancialElementController::class, 'index']);
    Route::post('/financial-elements', [FinancialElementController::class, 'store']);
    Route::put('/financial-elements/{id}', [FinancialElementController::class, 'update']);
    Route::delete('/financial-elements/{id}', [FinancialElementController::class, 'destroy']);

    Route::get('/admin-cashes', [AdminController::class, 'adminCashes']);

    Route::get('financial-order', [FinancialElementController::class, 'financialOrder']);
    Route::post('financial-order', [FinancialElementController::class, 'storeFinancialOrder']);
    Route::put('/financial-order/{id}', [FinancialElementController::class, 'update']);
    Route::delete('/financial-order/{id}', [FinancialElementController::class, 'destroyFinancialOrder']);
});

/**
 * Debug Route
 */
Route::middleware('auth:sanctum')->get('/debug-auth', function () {
    return response()->json([
        'user_id' => auth()->id(),
        'user_roles' => auth()->check() ? auth()->user()->roles->pluck('name') : [],
    ]);
});

/**
 * Packer Routes
 */
Route::middleware(['auth:sanctum', 'role:packer'])->group(function () {
    Route::get('history_orders', [OrderController::class, 'getHistoryOrders']);

    Route::get('/packer/orders', [OrderController::class, 'getPackerOrders']);
    Route::put('packer/orders/{orderId}/products', [OrderController::class, 'updateOrderProducts']);
    Route::get('packer/orders/{orderId}/', [OrderController::class, 'getDetailedOrder']);

    Route::post('/storeInvoice', [OrderController::class, 'storeInvoice']);
    Route::get('/getInvoice', [OrderController::class, 'getInvoice']);

    Route::post('/create_packer_document', [PackerController::class, 'create_packer_document']);
    Route::get('generalWarehouse', [PackerController::class, 'generalWarehouse']);
});

/**
 * Storager & Admin
 */
Route::middleware(['auth:sanctum', 'role:storager,admin'])->group(function () {
    Route::get('/product_subcards', [SubCardController::class, 'getSubCards']);
});

/**
 * Storager Only
 */
Route::middleware(['auth:sanctum', 'role:storager'])->group(function () {
    Route::get('/product_subcards_storager', [StorageController::class, 'getSubCards']);
    Route::get('getAllInstances', [StorageController::class, 'getAllInstances']);
    Route::post('/storeSales', [StorageController::class, 'storeSales']);
    Route::post('/storageReceivingBulkStore', [StorageController::class, 'storageReceivingBulkStore']);
    Route::get('fetchSalesReport', [StorageController::class, 'fetchSalesReport']);
    Route::get('general-warehouses', [StorageController::class, 'generalWarehouses']);
    Route::post('general-warehouses/write-off', [StorageController::class, 'writeOff']);
});

/**
 * Assign Packer to an Order
 */
Route::post('/orders/{orderId}/assign-packer', [OrderController::class, 'assignPacker']);

/**
 * Unit Measurements
 */
Route::get('/unit-measurements', [UnitMeasurementController::class, 'index']);

/**
 * Twilio Verification (HEAD)
 */
Route::post('/verify-phone', [AuthController::class, 'verifyPhone']);

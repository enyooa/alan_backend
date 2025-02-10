<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
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
use App\Http\Controllers\FavoritesController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PackerController;
use App\Http\Controllers\PriceRequestController;
use App\Http\Controllers\SubCardController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\FinancialElementController;
use App\Http\Controllers\PriceOfferController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WarehousesController;
use App\Models\FinancialOrder;

Route::post('/send-message', [ChatController::class, 'sendMessage']);
Route::get('messages', [ChatController::class, 'getMessages']);


Route::middleware(['auth:sanctum', 'role:courier'])->group(function () {
   Route::get('getCourierOrders', [CourierController::class, 'getCourierOrders']);
   Route::post('storeCourierDocument', [CourierController::class, 'storeCourierDocument']);
   Route::get('courier/orders/{orderId}', [CourierController::class, 'getCourierOrderDetails']);
   Route::post('/courier/orders/{orderId}/deliver', [CourierController::class, 'submitCourierDelivery']);
});
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);


Route::get('getCourierUsers',[CourierController::class,'getCourierUsers']);
Route::get('/packer_document/{id}', [PackerController::class, 'get_packer_document']);


Route::post('/admin/offer-requests', [AdminController::class, 'createOfferRequest']);
Route::get('/admin/offer-requests', [AdminController::class, 'getOfferRequests']);
Route::get('/admin/offer-requests/{id}', [AdminController::class, 'getOfferRequest']);

Route::middleware('auth:sanctum')->group(function () {
   Route::post('/upload-photo', [ProfileController::class, 'uploadPhoto']);
   Route::get('/profile', [ProfileController::class, 'getProfile']);
   Route::put('/profile', [ProfileController::class, 'updateProfile']);
   Route::post('/toggle-notifications', [UserController::class, 'toggleNotifications']); // ✅ Fix this
   Route::get('/user', [UserController::class, 'getUser']);
   Route::post('/logout', [AuthController::class,'logout']);

});

// страница администратора

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
   Route::post('product_card_create', [ProductCardController::class, 'store']);//создать карточку товара
   Route::get('/product_cards', [ProductCardController::class, 'getCardProducts']);
   Route::put('product_cards/{id}', [ProductCardController::class, 'updateCardProducts']);
   Route::delete('/product_cards/{id}', [ProductCardController::class, 'destroy']);

   //оприходование товаров
   Route::post('/admin_warehouses', [AdminController::class, 'createWarehouse']);
   Route::post('/receivingBulkStore', [AdminController::class, 'receivingBulkStore']);
   //оприходование товаров


   Route::post('/admin/product-groups', [AdminController::class, 'addProductToWarehouse']);
   Route::get('/warehouses/{id}/products', [AdminController::class, 'getProductsByWarehouse']);

   // остаток на складе
   
Route::get('/inventory/admin-warehouse', [WarehousesController::class, 'getRemainingQuantities']);
Route::post('/inventory/transfer', [WarehousesController::class, 'transferToGeneralWarehouse']);
   //подкарточки
   Route::post('/product_subcards', [SubCardController::class, 'store']); //подкарточки
   // Route::get('/product_subcards', [SubCardController::class, 'getSubCards']); 
   Route::put('/product_subcards/{id}', [SubCardController::class, 'update']);
   Route::delete('/product_subcards/{id}', [SubCardController::class, 'destroy']);
   //подкарточки
   //склад

   //создать поставщика
   Route::get('providers',[AdminController::class,'getProviders']);
   Route::post('create_providers', [AdminController::class,'storeProvider']);
   // Route::put('update_providers', [AdminController::class,'updateProvider']);
   // Route::delete('delete_providers', [AdminController::class,'deleteProvider']);
   //создать поставщика

   Route::put('/users/{user}/assign-roles', [UserController::class, 'assignRoles']);
   Route::delete('/users/{user}/remove-role', [UserController::class, 'removeRole']);

   Route::post('/create-account', [AuthController::class, 'createAccount']);

   Route::get('/users', [UserController::class, 'index']);
   Route::post('/users', [UserController::class, 'store']);
   Route::put('/users/{user}', [UserController::class, 'update']);
   Route::delete('/users/{user}', [UserController::class, 'destroy']);

   Route::get('/organizations', [OrganizationController::class, 'index']);
    Route::post('/organizations', [OrganizationController::class, 'store']);
    Route::put('/organizations/{organization}', [OrganizationController::class, 'update']);
    Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy']);
   
    // Unit Measurements Routes
    Route::get('/unit-measurements', [UnitMeasurementController::class, 'index']);
    Route::post('unit-measurements', [UnitMeasurementController::class, 'store']);
    Route::put('/unit-measurements/{unit}', [UnitMeasurementController::class, 'update']);
    Route::delete('/unit-measurements/{unit}', [UnitMeasurementController::class, 'destroy']);

    // создать адрес клиентов
    // создать адрес клиентов

   //  создать продажу
   // 
   Route::get('getSalesWithDetails', [SalesController::class, 'getSalesWithDetails']);

   Route::post('sales', [SalesController::class, 'store']);
   Route::post('bulk_sales', [SalesController::class, 'bulkStore']);
   Route::put('/sales/{id}', [SalesController::class, 'update']);
   Route::delete('/sales/{id}', [SalesController::class, 'destroy']);

   // создать продажу
   // создать ценовое предложение
   Route::post('bulkPriceOffers', [PriceOfferController::class, 'bulkPriceOffers']);

      // создать ценовое предложение

   // Инвентаризация склада

   // справочник в админке
   Route::get('operations-history', [AdminController::class, 'fetchOperationsHistory']);
   Route::put('operations/{id}/{type}', [AdminController::class, 'updateOperation']);
   Route::delete('operations/{id}/{type}', [AdminController::class, 'deleteOperation']);
   // справочник в админке


   Route::get('getStorageUsers',[StorageController::class,'getStorageUsers']);
   Route::post('bulkStoreInventory',[StorageController::class,'bulkStoreInventory']);
   Route::get('getInventory',[StorageController::class,'getInventory']);



   Route::post('storeAdress/{userId}', [AddressController::class, 'storeAdress']);
   Route::get('getClientAdresses',[AddressController::class, 'getClientAddresses']);
   Route::get('getStorageUserAddresses',[AddressController::class, 'getStorageUserAddresses']);

   Route::get('fetch_data_of_price_offer',[PriceOfferController::class,'fetch_data_of_price_offer'])->name('fetch_data_of_price_offer');  
});
Route::middleware(['auth:sanctum', 'role:client,admin'])->group(function () {
   Route::get('getClientAdresses',[AddressController::class, 'getClientAddresses']);

});

Route::middleware(['auth:sanctum', 'role:client'])->group(function () {
     // Route::get('sales', [SalesController::class, 'index']);
   Route::get('/product-data', [ClientController::class, 'getAllProductData']);
   // Route::get('getUserPriceRequests', [PriceRequestController::class, 'getUserPriceRequests']);
   Route::get('getSalesClientPage', [SalesController::class, 'getSalesWithDetails']);
   Route::get('/product_subcards_for_clientpage', [SubCardController::class, 'getSubCards']);
   Route::get('/product_cards_for_clientpage', [ProductCardController::class, 'getCardProducts']);
   Route::post('/confirm-courier-document', [CourierController::class, 'confirmCourierDocument']);
   Route::get('/client-order-items', [ClientController::class, 'getClientOrderItems']);


   Route::get('basket', [BasketController::class, 'index']);
   Route::post('basket/add', [BasketController::class, 'add']);
   Route::post('basket/remove', [BasketController::class, 'remove']);
   Route::post('basket/clear', [BasketController::class, 'clear']);
   Route::post('basket/place-order', [BasketController::class, 'placeOrder']);

   Route::get('getFavorites', [FavoritesController::class, 'getFavorites']);
   Route::post('addToFavorites', [FavoritesController::class, 'addToFavorites']);
   Route::post('removeFromFavorites', [FavoritesController::class, 'removeFromFavorites']);


});

Route::middleware(['auth:sanctum', 'role:cashbox,admin'])->group(function () {
   Route::get('/financial-elements', [FinancialElementController::class, 'index']);
   Route::post('/financial-elements', [FinancialElementController::class, 'store']);
   Route::put('/financial-elements/{id}', [FinancialElementController::class, 'update']);
   Route::delete('/financial-elements/{id}', [FinancialElementController::class, 'destroy']);

   Route::get('/admin-cashes', [AdminController::class, 'adminCashes']);

   
      Route::get('financial-order', [FinancialElementController::class, 'financialOrder']); // List all financial orders
      Route::post('financial-order', [FinancialElementController::class, 'storeFinancialOrder']); // 
      Route::put('/financial-order/{id}', [FinancialElementController::class, 'update']);
      Route::delete('/financial-order/{id}', [FinancialElementController::class, 'destroyFinancialOrder']);

      //Route::get('/{id}', [FinancialElementController::class, 'showFinancialOrder']); // Get a single financial order
      //Route::delete('/{id}', [FinancialElementController::class, 'destroyFinancialOrder']); // Delete a financial order
 
});




Route::middleware('auth:sanctum')->get('/debug-auth', function () {
   return response()->json([
       'user_id' => auth()->id(),
       'user_roles' => auth()->check() ? auth()->user()->roles->pluck('name') : [],
   ]);
});

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
Route::middleware(['auth:sanctum', 'role:storager,admin'])->group(function () {
   Route::get('/product_subcards', [SubCardController::class, 'getSubCards']); 

});

Route::middleware(['auth:sanctum', 'role:storager,admin'])->group(function () {
   
   Route::get('getAllInstances', [StorageController::class, 'getAllInstances']);
   Route::post('/storeSales', [StorageController::class, 'storeSales']);
   Route::post('/storageReceivingBulkStore', [StorageController::class, 'storageReceivingBulkStore']);
   Route::get('fetchSalesReport', [StorageController::class, 'fetchSalesReport']);

   Route::get('general-warehouses', [StorageController::class, 'generalWarehouses']);
   Route::post('general-warehouses/write-off', [StorageController::class, 'writeOff']);
      
});

// присвоить фасовщика заказу
Route::post('/orders/{orderId}/assign-packer', [OrderController::class, 'assignPacker']);
Route::get('/unit-measurements', [UnitMeasurementController::class, 'index']);











Route::get('client-users', [AdminController::class, 'getClientUsers']);

Route::get('/users', [UserController::class, 'index']);
   Route::post('/users', [UserController::class, 'store']);
   Route::put('/users/{user}', [UserController::class, 'update']);
   Route::delete('/users/{user}', [UserController::class, 'destroy']);

   Route::get('/organizations', [OrganizationController::class, 'index']);
    Route::post('/organizations', [OrganizationController::class, 'store']);
    Route::put('/organizations/{organization}', [OrganizationController::class, 'update']);
    Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy']);

  
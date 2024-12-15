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

Route::middleware(['auth:sanctum', 'role:courier'])->group(function () {
   Route::get('getCourierDocuments', [CourierController::class, 'getCourierDocuments']);
   Route::post('/send-message', [ChatController::class, 'sendMessage']);
   Route::get('messages', [ChatController::class, 'getMessages']);

});
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class,'logout']);


Route::get('getCourierUsers',[CourierController::class,'getCourierUsers']);
Route::get('/get_packer_document', [PackerController::class, 'get_packer_document']);


Route::post('/admin/offer-requests', [AdminController::class, 'createOfferRequest']);
Route::get('/admin/offer-requests', [AdminController::class, 'getOfferRequests']);
Route::get('/admin/offer-requests/{id}', [AdminController::class, 'getOfferRequest']);


// это чтобы всех ролей собрать
// Route::middleware('auth:sanctum')->get('/user/roles', function () {
//    return auth()->user()->roles->pluck('name');
// });
// все роли собирать заканчивается ветка
// Route::middleware('auth:sanctum')->post('/upload-photo', [AuthController::class, 'uploadPhoto']);


// страница администратора

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
   Route::post('product_card_create', [ProductCardController::class, 'store']);//создать карточку товара
   Route::get('/product_cards', [ProductCardController::class, 'getCardProducts']);

   Route::post('price-offers', [PriceRequestController::class, 'store']);
   Route::post('bulkPriceOffers', [PriceRequestController::class, 'bulkStore']);

   
   //оприходование товаров
   Route::post('/admin_warehouses', [AdminController::class, 'createWarehouse']);
   Route::post('/receivingBulkStore', [AdminController::class, 'receivingBulkStore']);
   //оприходование товаров
   // Warehouse Routes
   Route::get('/admin_warehouses', [AdminController::class, 'getAllWarehouses']);


   Route::post('/admin/product-groups', [AdminController::class, 'addProductToWarehouse']);
   Route::get('/warehouses/{id}/products', [AdminController::class, 'getProductsByWarehouse']);

//admin routes end
   
   //подкарточки
   Route::post('/product_subcards', [SubCardController::class, 'store']); //подкарточки
   Route::get('/product_subcards', [SubCardController::class, 'getSubCards']); 
   //подкарточки

   //создать поставщика
   Route::get('providers',[AdminController::class,'getProviders']);
   Route::post('create_providers', [AdminController::class,'storeProvider']);
   // Route::put('update_providers', [AdminController::class,'updateProvider']);
   // Route::delete('delete_providers', [AdminController::class,'deleteProvider']);
   //создать поставщика

   Route::put('/users/{user}/assign-roles', [UserController::class, 'assignRoles']);
   Route::delete('/users/{user}/remove-role', [UserController::class, 'removeRole']);

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
    Route::post('sales', [SalesController::class, 'store']);
   Route::post('bulk_sales', [SalesController::class, 'bulkStore']);

   // создать продажу
   
   // Инвентаризация склада
   Route::get('client-users', [AdminController::class, 'getClientUsers']);
   Route::get('operations-history', [AdminController::class, 'fetchOperationsHistory']);

   Route::get('getStorageUsers',[StorageController::class,'getStorageUsers']);
   Route::post('bulkStoreInventory',[StorageController::class,'bulkStoreInventory']);
   Route::get('getInventory',[StorageController::class,'getInventory']);



   Route::post('storeAdress/{userId}', [AddressController::class, 'storeAdress']);
   Route::get('getClientAdresses',[AddressController::class, 'getClientAddresses']);
   Route::get('getStorageUserAddresses',[AddressController::class, 'getStorageUserAddresses']);

   
});
Route::middleware(['auth:sanctum', 'role:admin,client'])->group(function () {
   Route::get('getSalesWithDetails', [SalesController::class, 'getSalesWithDetails']);
   Route::get('/product_subcards', [SubCardController::class, 'getSubCards']);
   Route::get('/product_cards', [ProductCardController::class, 'getCardProducts']);


});

Route::middleware(['auth:sanctum', 'role:client'])->group(function () {
     // Route::get('sales', [SalesController::class, 'index']);
     Route::get('/product-data', [ClientController::class, 'getAllProductData']);
     Route::get('getUserPriceRequests', [PriceRequestController::class, 'getUserPriceRequests']);

     Route::get('basket', [BasketController::class, 'index']);
    Route::post('basket/add', [BasketController::class, 'add']);
    Route::post('basket/remove', [BasketController::class, 'remove']);
    Route::post('basket/clear', [BasketController::class, 'clear']);
    Route::post('basket/place-order', [BasketController::class, 'placeOrder']);

    Route::post('addToFavorites', [FavoritesController::class, 'addToFavorites']);
    Route::post('removeFromFavorites', [FavoritesController::class, 'removeFromFavorites']);
    Route::get('getFavorites', [FavoritesController::class, 'getFavorites']);

    Route::get('getFavorites', [FavoritesController::class, 'getFavorites']);
    Route::post('addToFavorites', [FavoritesController::class, 'addToFavorites']);
    Route::post('removeFromFavorites', [FavoritesController::class, 'removeFromFavorites']);

    

});

Route::middleware(['auth:sanctum', 'role:cashbox'])->group(function () {
   Route::get('/financial-elements', [FinancialElementController::class, 'index']);
   Route::post('/financial-elements', [FinancialElementController::class, 'store']);
   Route::put('/financial-elements/{id}', [FinancialElementController::class, 'update']);
   Route::delete('/financial-elements/{id}', [FinancialElementController::class, 'destroy']);

   Route::get('/admin-cashes', [AdminController::class, 'adminCashes']);
   Route::get('client-users', [AdminController::class, 'getClientUsers']);

   
      Route::get('financial-order', [FinancialElementController::class, 'financialOrder']); // List all financial orders
      Route::post('financial-order', [FinancialElementController::class, 'storeFinancialOrder']); // Create a financial order
      Route::get('/{id}', [FinancialElementController::class, 'showFinancialOrder']); // Get a single financial order
      Route::delete('/{id}', [FinancialElementController::class, 'destroyFinancialOrder']); // Delete a financial order
  
   
   
   

});




Route::middleware('auth:sanctum')->get('/debug-auth', function () {
   return response()->json([
       'user_id' => auth()->id(),
       'user_roles' => auth()->check() ? auth()->user()->roles->pluck('name') : [],
   ]);
});

Route::middleware(['auth:sanctum', 'role:packer'])->group(function () {

   Route::get('/packer/orders', [OrderController::class, 'getPackerOrders']);
   Route::put('packer/orders/{orderId}/products', [OrderController::class, 'updateOrderProducts']);
   Route::get('packer/orders/{orderId}/', [OrderController::class, 'getDetailedOrder']);

   Route::post('/storeInvoice', [OrderController::class, 'storeInvoice']);
   Route::get('/getInvoice', [OrderController::class, 'getInvoice']);

   Route::post('/create_packer_document', [PackerController::class, 'create_packer_document']);

   
   Route::get('generalWarehouse', [PackerController::class, 'generalWarehouse']);
});


// присвоить фасовщика заказу
Route::post('/orders/{orderId}/assign-packer', [OrderController::class, 'assignPacker']);












Route::get('/users', [UserController::class, 'index']);
   Route::post('/users', [UserController::class, 'store']);
   Route::put('/users/{user}', [UserController::class, 'update']);
   Route::delete('/users/{user}', [UserController::class, 'destroy']);

   Route::get('/organizations', [OrganizationController::class, 'index']);
    Route::post('/organizations', [OrganizationController::class, 'store']);
    Route::put('/organizations/{organization}', [OrganizationController::class, 'update']);
    Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy']);

  
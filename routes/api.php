    <?php
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\AdminController;
    use App\Http\Controllers\UserController;
    use App\Http\Controllers\UnitMeasurementController;
    use App\Http\Controllers\OrganizationController;
    use App\Http\Controllers\ProductCardController;
    use App\Http\Controllers\AddressController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BasketController;
    use App\Http\Controllers\ChatController;
    use App\Http\Controllers\ClientController;
    use App\Http\Controllers\CourierController;
    use App\Http\Controllers\DocumentController;
    use App\Http\Controllers\ExpenseController;
    use App\Http\Controllers\FavoritesController;
    use App\Http\Controllers\OrderController;
    use App\Http\Controllers\PackerController;
    use App\Http\Controllers\PriceRequestController;
    use App\Http\Controllers\SubCardController;
    use App\Http\Controllers\WriteOffController;
    use App\Http\Controllers\SalesController;
    use App\Http\Controllers\StorageController;
    use App\Http\Controllers\FinancialElementController;
    use App\Http\Controllers\PriceOfferController;  // HEAD line
    use App\Http\Controllers\ProductController;      // HEAD line
    use App\Http\Controllers\ProfileController;      // HEAD line
    use App\Http\Controllers\ReferenceController;    // HEAD line
    use App\Http\Controllers\ReportsController;
    use App\Http\Controllers\WarehousesController;
    use App\Http\Controllers\WhatsAppCheckController;
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
    Route::post('/check-whatsapp', [WhatsAppCheckController::class, 'checkWhatsApp']);
    Route::post('/send_verification_code', [AuthController::class, 'sendVerificationCode']);
    Route::post('/verify_code', [AuthController::class, 'verifyCode']);
    /**
        * Courier Users
        */

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
    Route::get('/users/adminOrStorager', [UserController::class, 'getAdminsAndStoragers']);
    Route::post('/receivingBulkStore', [AdminController::class, 'storeIncome']);
    Route::post('/storeIncomes', [AdminController::class, 'storeIncomes']);

    Route::get('warehouse-items', [WarehousesController::class, 'getWarehouseItems']);

    // Additional Admin
    Route::post('/admin/product-groups', [AdminController::class, 'addProductToWarehouse']);
    Route::get('/warehouses/{id}/products', [AdminController::class, 'getProductsByWarehouse']);


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
    // "Плоская история"
    Route::get('/documents/allHistories', [ProductController::class, 'allHistories']);

    // Получение списка документов
    Route::get('/documents', [ProductController::class, 'index']);
    // Получение одного документа (с items и expenses)
    // Обновление документа
    Route::put('/documents/{document}', [ProductController::class, 'update']);
    // Удаление документа
    Route::delete('/documents/{document}', [ProductController::class, 'destroy']);


    /**
    * References
    */
    Route::get('/references/{type}', [ReferenceController::class, 'fetch']);
    Route::patch('/references/{type}/{id}', [ReferenceController::class, 'update']);
    Route::delete('/references/{type}/{id}', [ReferenceController::class, 'destroy']);
    Route::get('/references/{type}/{id}', [ReferenceController::class, 'fetchOne']);

   /**
    * Storage
    */
    Route::get('getStorageUsers',[StorageController::class,'getStorageUsers']);
   // Инициализация для перемещения (получение остатков "От кого" user)
    Route::get('/transfer/init', [DocumentController::class, 'initTransfer']);

    // Сохранение перемещения
    Route::post('/transfer/store', [DocumentController::class, 'storeTransfer']);

        // DELETE /api/writeoff/{id} => remove

    // 2) Для сохранения списания:
    Route::post('/writeoff/store', [DocumentController::class, 'storeWriteOff']);
    // для мобильного
    Route::post('storeWriteOff', [AdminController::class, 'storeWriteOff']);


    // Сохранение списания

    // Получить «все документы» (для истории)

    Route::post('sendToGeneralWarehouse',[StorageController::class,'sendToGeneralWarehouse']);
    Route::get('getInventory',[StorageController::class,'getInventory']);
    Route::post('bulkStoreInventory',[StorageController::class,'bulkStoreInventory']);

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
    // 1) Список складов
    Route::get('/warehouses', [ReportsController::class, 'index']);

    // 2) Отчёт
    Route::get('admin-report-debts', [ReportsController::class, 'debtsReport']);
    Route::get('sales-report', [ReportsController::class, 'getSalesReport']);
    Route::get('/reports/sales/pdf', [ReportsController::class, 'exportPdf']);
    Route::get('/reports/sales/excel', [ReportsController::class, 'exportExcel']);
    Route::get('storage-report', [ReportsController::class, 'getStorageReport']);

    });

    // общая ветка склада и админа
    Route::middleware(['auth:sanctum', 'role:storager,admin'])->group(function () {
        Route::get('getWarehouses',[WarehousesController::class, 'getWarehouses']);
        Route::get('/documents/{document}', [ProductController::class, 'show']);
        Route::get('getWarehouseDetails', [WarehousesController::class, 'getWarehouseDetails']);
        Route::put('/writeoff_update/{document}', [WriteOffController::class, 'update']);
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
    Route::put('/orders/{orderId}/confirm', [OrderController::class, 'confirmOrder']);
    Route::get('/client-orders', [ClientController::class, 'getClientOrders']);

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
    Route::get('report_debs', [ClientController::class, 'report_debs']);

    // cashbox
    Route::post('financial-order', [FinancialElementController::class, 'storeFinancialOrder']);

    });

    /**
     * Cashbox & Admin
     */
    Route::middleware(['auth:sanctum', 'role:cashbox,admin'])->group(function () {
    Route::post('/financial-elements', [FinancialElementController::class, 'store']);
    Route::put('/financial-elements/{id}', [FinancialElementController::class, 'update']);
    Route::delete('/financial-elements/{id}', [FinancialElementController::class, 'destroy']);
    Route::get('/financial-elements', [FinancialElementController::class, 'index']);

    Route::get('/client-users', [AdminController::class, 'getClientUsers']);

    Route::get('providers',[AdminController::class,'getProviders']);
    Route::post('financial-orders', [FinancialElementController::class, 'storeFinancialOrders']);

    Route::get('financial-order', [FinancialElementController::class, 'financialOrder']);
    Route::put('/financial-order/{id}', [FinancialElementController::class, 'update']);
    Route::delete('/financial-order/{id}', [FinancialElementController::class, 'destroyFinancialOrder']);
    });
    Route::middleware(['auth:sanctum', 'role:cashbox,client'])->group(function () {
    Route::get('/admin-cashes', [AdminController::class, 'adminCashes']);
    Route::get('/financial-elements', [FinancialElementController::class, 'index']);

});

/**
 * Packer Routes
 */
Route::middleware(['auth:sanctum', 'role:packer'])->group(function () {
    Route::get('history_orders', [OrderController::class, 'getHistoryOrders']);
    Route::get('/packer_document/{id}', [PackerController::class, 'get_packer_document']);

    Route::get('/packer/orders', [OrderController::class, 'getPackerOrders']);
    Route::put('packer/orders/{orderId}/products', [OrderController::class, 'updateOrderProducts']);
    Route::get('packer/orders/{orderId}/', [OrderController::class, 'getDetailedOrder']);

    Route::post('/storeInvoice', [OrderController::class, 'storeInvoice']);
    Route::get('/getInvoice', [OrderController::class, 'getInvoice']);

    Route::post('/create_packer_document', [PackerController::class, 'create_packer_document']);
    Route::get('generalWarehouse', [PackerController::class, 'generalWarehouse']);
    Route::get('getAllPackerInstances',[PackerController::class,'getAllInstances']);
    Route::get('getPackerReportPage',[PackerController::class,'getManagerWarehouseReport']);
});

/**
 * Ветки складовщика
 */
Route::middleware(['auth:sanctum', 'role:storager'])->group(function () {

    Route::get('/product_subcards_storager', [StorageController::class, 'getSubCards']);
    Route::get('getAllInstances', [StorageController::class, 'getAllInstances']);
    Route::post('/storeSales', [StorageController::class, 'storeSales']);
    Route::get('getStorageSales',[StorageController::class,'getStorageSales']);

    Route::post('storageReceivingBulkStore', [StorageController::class, 'storeIncomeAsWarehouseManager']);

    Route::get('/receipts/{docId}/with-references', [StorageController::class, 'getReceiptWithReferences']);

    Route::post('storageSalesBulkStore', [StorageController::class, 'storageSalesBulkStore']);
    Route::delete('/deleteSale/{id}', [StorageController::class, 'deleteSale']);
    Route::put('/updateSale/{id}', [StorageController::class, 'updateSale']);

    Route::get('fetchSalesReport', [StorageController::class, 'fetchSalesReport']);
    Route::get('general-warehouses', [StorageController::class, 'generalWarehouses']);
    Route::get('storage_report',[StorageController::class, 'getReport']);
    // mobile version income product
    Route::get('getAllReceipts', [StorageController::class, 'getAllReceipts']);

    Route::put('updateReceipt/{id}', [StorageController::class, 'updateReceipt']);
    Route::delete('deleteReceipt/{id}', [StorageController::class, 'deleteReceipt']);

    // mobile version fetch
    Route::get('writeoff_fetch',[WriteOffController::class, 'writeoff_fetch']);
    Route::prefix('writeoff')->group(function() {
        // GET /api/writeoff  => list all "write off" docs
        Route::get('/', [WriteOffController::class, 'index']);
        // GET /api/writeoff/{id} => single doc
        Route::get('/{id}', [WriteOffController::class, 'show']);
        // POST /api/writeoff => create new
        Route::post('/', [WriteOffController::class, 'store']);
        // PUT /api/writeoff/{id} => update
        Route::put('/{id}', [WriteOffController::class, 'update']);
        // DELETE /api/writeoff/{id} => remove
        Route::delete('/{id}', [WriteOffController::class, 'destroy']);
    });

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

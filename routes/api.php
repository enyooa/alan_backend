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
    use App\Http\Controllers\InventoryController;
    use App\Http\Controllers\SalesClientController;

    use App\Http\Controllers\OrderController;
    use App\Http\Controllers\PackerController;
    use App\Http\Controllers\PriceRequestController;
    use App\Http\Controllers\SubCardController;
    use App\Http\Controllers\WriteOffController;
    use App\Http\Controllers\SalesController;
    use App\Http\Controllers\StorageController;
    use App\Http\Controllers\FinancialElementController;
use App\Http\Controllers\FinancialSummaryController;
use App\Http\Controllers\IncomesController;
use App\Http\Controllers\PermissionAssignmentController;
use App\Http\Controllers\PriceOfferController;  // HEAD line
    use App\Http\Controllers\ProductController;      // HEAD line
    use App\Http\Controllers\ProfileController;      // HEAD line
    use App\Http\Controllers\ReferenceController;    // HEAD line
    use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SalesIncomesController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WarehousesController;
    use App\Http\Controllers\WhatsAppCheckController;
use App\Http\Controllers\WriteoffIncomesController;
use App\Http\Controllers\TransferIncomesController;
use App\Http\Controllers\AdminCashController;



use App\Models\FinancialOrder;

    /**
     * Chat Routes
     */

    /**
     * Courier Routes
     */
    Route::middleware(['auth:sanctum', 'role:courier,superadmin'])->group(function () {
        Route::post('/send-message', [ChatController::class, 'sendMessage']);
        Route::get('messages', [ChatController::class, 'getMessages']);

    Route::get('getCourierOrders', [CourierController::class, 'getCourierOrders']);
    Route::post('storeCourierDocument', [CourierController::class, 'storeCourierDocument']);
    Route::get('courier/orders/{orderId}', [CourierController::class, 'getCourierOrderDetails']);
    Route::post('/courier/orders/{orderId}/deliver', [CourierController::class, 'submitCourierDelivery']);
    });


    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register_organization_with_user',[AuthController::class,'registerOrganization']);
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
        Route::post('/plan/buy', [SubscriptionController::class, 'buyPlan']);

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
    Route::middleware(['auth:sanctum', 'role:admin,superadmin,storager'])->group(function () {
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
    // Route::post('sales', [SalesController::class, 'store']);
    Route::post('sales', [SalesClientController::class, 'bulkStore']);
    Route::put('/sales/{sale}',  // <-- Указываем {sale}, а не {id}
     [SalesClientController::class, 'update']);
    Route::delete('/sales/{sale}', [SalesClientController::class, 'destroy']);

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

    Route::put('/documents/{document}', [ProductController::class, 'update']);
    // Удаление документа
    Route::delete('/documents/{document}', [ProductController::class, 'destroy']);
    Route::put('/updateIncome/{id}', [ProductController::class, 'updateMobileIncome']);


    /**
    * References
    */
    Route::get('/references/{type}', [ReferenceController::class, 'fetch']);
    Route::patch('/references/{type}/{id}', [ReferenceController::class, 'update']);
    Route::delete('/reference', [ReferenceController::class, 'bulkDestroy']);   // bulk
    Route::get('/references/{type}/{id}', [ReferenceController::class, 'fetchOne']);
    Route::delete('/references/{type}/{id}', [ReferenceController::class, 'destroyOne']);
    Route::patch('/references',               [ReferenceController::class, 'bulkUpdate']);      // массив разных типов
Route::patch('/references/{type}',        [ReferenceController::class, 'bulkUpdate']);      // массив одного типа


    Route::get('getStorageUsers',[StorageController::class,'getStorageUsers']);
   // Инициализация для перемещения (получение остатков "От кого" user)
//    старая ветка перемещение
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
    Route::middleware(['auth:sanctum', 'role:storager,admin,superadmin'])->group(function () {
        Route::get('getWarehouses',[WarehousesController::class, 'getWarehouses']);
        Route::get('/documents/{document}', [ProductController::class, 'show']);
        Route::get('getWarehouseDetails', [WarehousesController::class, 'getWarehouseDetails']);
        Route::put('/writeoff_update/{document}', [WriteOffController::class, 'update']);
    });
    /**
     * Client & Admin (shared) Routes
     */
    Route::middleware(['auth:sanctum', 'role:client,admin,superadmin'])->group(function () {
        Route::get('getClientAdresses',[AddressController::class, 'getClientAddresses']);
        Route::get('sales', [SalesClientController::class, 'getSalesWithDetails']);

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
    // routes/api.php
    Route::delete('basket/{id}', [BasketController::class, 'destroy']);
    Route::post('basket/clear', [BasketController::class, 'clear']);
    Route::post('basket/place-order', [BasketController::class, 'placeOrder']);
    Route::post('/basket/quantity', [BasketController::class, 'changeQuantity']);


    // Favorites
    Route::get('getFavorites', [FavoritesController::class, 'getFavorites']);
    Route::post('addToFavorites', [FavoritesController::class, 'addToFavorites']);
    // routes/api.php
    Route::delete(
        'favorites/{favorite}',   // {favorite} → ID из таблицы favorites
        [FavoritesController::class, 'destroy']
    );
    Route::get('report_debs', [ClientController::class, 'report_debs']);
    // routes/api.php
    Route::post('/favorites/{favorite}/add-to-basket',       // ← {favorite} is the ID in the favorites table
    [FavoritesController::class, 'addToBasket']
    );

    // cashbox
    Route::post('financial-order', [FinancialElementController::class, 'storeFinancialOrders']);

    });

    /**
     * Cashbox & Admin
     */
    Route::middleware(['auth:sanctum', 'role:cashbox,admin,superadmin'])->group(function () {
        Route::get('/reference/cashbox', [ReferenceController::class,'cashbox']);   // NEW

    });
    Route::middleware(['auth:sanctum', 'role:cashbox,client,superadmin'])->group(function () {
    Route::get('/financial-elements', [FinancialElementController::class, 'index']);

});

/**
 * Packer Routes
 */
Route::middleware(['auth:sanctum', 'role:packer,superadmin'])->group(function () {
    Route::get('history_orders', [OrderController::class, 'getHistoryOrders']);
    Route::get('/packer_document/{id}', [PackerController::class, 'get_packer_document']);

    Route::get('/packer/orders', [OrderController::class, 'getPackerOrders']);
    Route::get('/packer/orders/history', [OrderController::class, 'getPackerHistory']);

    Route::put('packer/orders/{orderId}/products', [OrderController::class, 'updateOrderProducts']);
    Route::get('packer/orders/{orderId}/', [OrderController::class, 'getDetailedOrder']);
// старая
    Route::post('/storeInvoice', [OrderController::class, 'storeInvoice']);
    Route::get('/getInvoice', [OrderController::class, 'getInvoice']);
 // старая
    Route::post('/create_packer_document', [PackerController::class, 'create_packer_document']);
    Route::get('generalWarehouse', [PackerController::class, 'generalWarehouse']);
    Route::get('getAllPackerInstances',[PackerController::class,'getAllInstances']);
    Route::get('getPackerReportPage',[PackerController::class,'getManagerWarehouseReport']);

    Route::get('/couriers', [PackerController::class, 'allCouriers']);

});

/**
 * Ветки складовщика
 */
Route::middleware(['auth:sanctum', 'role:storager,superadmin'])->group(function () {

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
    Route::middleware(['auth:sanctum', 'role:superadmin,admin,cashbox,storager'])->group(function () {

    Route::prefix('financial-summary')->group(function () {
        Route::get('day',   [FinancialSummaryController::class, 'day']);    // ?date=YYYY‑MM‑DD
        Route::get('week',  [FinancialSummaryController::class, 'week']);   // ?date=YYYY‑MM‑DD (любой день недели)
        Route::get('month', [FinancialSummaryController::class, 'month']);  // ?year=YYYY&month=MM
        Route::get('year',  [FinancialSummaryController::class, 'year']);   // ?year=YYYY
    });

    Route::get('report-debts', [ReportsController::class, 'debtsReport']);
    Route::get('report-sales', [ReportsController::class, 'getSalesReport']);
    Route::get('/reports/sales/pdf', [ReportsController::class, 'exportPdf']);
    Route::get('/reports/sales/excel', [ReportsController::class, 'exportExcel']);
    Route::get('report-warehouses', [ReportsController::class, 'getStorageReport']);

    // справочники

    Route::get('/reference', [ReferenceController::class,'index']);   // NEW
    Route::get('/reference/{type}', [ReferenceController::class, 'getReferencesByType']);
    Route::post('/reference', [ReferenceController::class, 'bulkStore']);

    Route::patch('/reference/{id}', [ReferenceController::class, 'bulkUpdate']);
    Route::delete('/reference/{id}', [ReferenceController::class, 'destroy']);

    Route::get('/reference/{type}/{id}', [ReferenceController::class, 'fetchOne']);
    Route::patch('/refferences/{id}', [ReferenceController::class, 'updateWithItems']);


    Route::get   ('permissions',                [PermissionAssignmentController::class,'catalog']);
    Route::get   ('users/{user}/permissions',                [PermissionAssignmentController::class,'index']);
    Route::post  ('users/{user}/permissions',                [PermissionAssignmentController::class,'store']);
    Route::delete('users/{user}/permissions/{code}',         [PermissionAssignmentController::class,'destroy']);
    Route::put   ('users/{user}/permissions',        [PermissionAssignmentController::class,'update']);

    // отчеты
    Route::get('cash-report', [ReportsController::class, 'cash_report']);

    // сотрудники
    Route::get('stuff', [UserController::class, 'stuff']);
    Route::put('/users/{user}/roles-permissions', [UserController::class,'updateStuff']);

    // фин ордер
    Route::post('/financial-elements', [FinancialElementController::class, 'store']);
    Route::put    ('/financial-elements/{element}',    [FinancialElementController::class, 'update' ]);
    Route::patch  ('/financial-elements/{element}',    [FinancialElementController::class, 'update' ]);
    Route::delete ('/financial-elements/{element}',    [FinancialElementController::class, 'destroy']);
    Route::get('/financial-elements',                [FinancialElementController::class, 'index']);

    // only “income” or only “expense” for the current user
    Route::get('/financial-elements/{type}',         [FinancialElementController::class, 'byType'])
     ->where('type', 'income|expense');
     Route::get ('/get-cashes', [AdminCashController::class, 'index']);
     Route::post('/create-cashes', [AdminCashController::class, 'store']);    //  new Superadmin uses 24.04.2025
    Route::get('/client-users', [AdminController::class, 'getClientUsers']);

    Route::get('/client-users', [AdminController::class, 'getClientUsers']);
// поставщики
    Route::get('providers',[AdminController::class,'getProviders']);
// счета админа
    Route::get('/admin-cashes', [AdminController::class, 'adminCashes']);

    // Route::post('financial-order', [FinancialElementController::class, 'storeFinancialOrder']);
    Route::post('financial-orders', [FinancialElementController::class, 'storeFinancialOrders']);

    Route::get('financial-orders', [FinancialElementController::class, 'financialOrder']);
    Route::get('financial-orders/{type}',         [FinancialElementController::class, 'financialOrderByType'])
     ->where('type', 'income|expense');
    Route::put('/financial-order/{id}', [FinancialElementController::class, 'updateFinancialOrder']);
    Route::delete('/financial-order/{id}', [FinancialElementController::class, 'destroyFinancialOrder']);



    // карточка товара
    Route::get('/product-cards', [ProductCardController::class, 'index']);
    // подкарточка товара
    Route::get('/product-subcards', [SubCardController::class, 'getSubCards']);


    // Документы
    // Route::get('/documents-all', [ProductController::class, 'allHistories']);
    // приход товара
    Route::get('income-products',[DocumentController::class,'indexIncomes']);
    //приход товара
    Route::post('/income-products', [IncomesController::class, 'storeIncomes']);
    Route::put('/income-products/{document}', [IncomesController::class, 'updateIncomes'])
    ->whereNumber('document');
    Route::delete('/income-products/{document}',        // URL
    [IncomesController::class,'destroyIncomes'])   // method
->whereNumber('document');

    // продажа

    Route::get('sales-products',[SalesIncomesController::class,'indexSales']);
    Route::post('sales-products',[SalesIncomesController::class,'postSales']);
    Route::put('/sales-products/{document}', [SalesIncomesController::class, 'updateSales'])
     ->whereNumber('document');

    Route::delete('/sales-products/{document}',        // URL
    [SalesIncomesController::class,'destroySales'])   // method
    ->whereNumber('document');

    // списание
    Route::post('writeoff-products',[WriteoffIncomesController::class,'postWriteOff']);
    Route::put('/writeoff-products/{document}', [WriteoffIncomesController::class, 'updateWriteOff'])
    ->whereNumber('document');

    Route::get('writeoff-products',[WriteoffIncomesController::class,'indexWriteOff']);

    Route::delete('/writeoff-products/{document}', [WriteoffIncomesController::class, 'deleteWriteOff'])
        ->whereNumber('document');

    // перемещение
    Route::get('/transfer-products', [TransferIncomesController::class,'indexTransfers']);
    Route::post('/transfer-products',       [TransferIncomesController::class,'storeTransfer']);

    /* обновить */
    Route::put('transfer-products/{document}', [TransferIncomesController::class,'updateTransfer'])
         ->whereNumber('document');

    /* удалить */
    Route::delete('transfer-products/{document}', [TransferIncomesController::class,'destroyTransfer'])
         ->whereNumber('document');

    Route::prefix('price-offers')->group(function () {

    Route::get('/',               [PriceRequestController::class,'index']);

    Route::post('/',              [PriceRequestController::class,'store']);

    Route::get('{order}',         [PriceRequestController::class,'show'])
            ->whereNumber('order');

    Route::put('{order}',         [PriceRequestController::class,'update'])
            ->whereNumber('order');

    Route::delete('{order}',      [PriceRequestController::class,'destroy'])
            ->whereNumber('order');
});

    /**  инвентаризация  */
    Route::get(   '/inventory-products',              [InventoryController::class,'index']);
    Route::get(   '/inventory-products/{document}',   [InventoryController::class,'show'])->whereNumber('document');
    Route::post(  '/inventory-products',              [InventoryController::class,'store']);
    Route::put(   '/inventory-products/{document}',   [InventoryController::class,'update'])->whereNumber('document');
    Route::delete('/inventory-products/{document}',   [InventoryController::class,'destroy'])->whereNumber('document');

});

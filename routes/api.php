<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ======================
// Auth Routes
// ======================
use App\Http\Controllers\Auth\AuthController;
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/find-account', [AuthController::class, 'findAccount']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::post('/logout-device', [AuthController::class, 'logoutDevice']);
        Route::get('/devices', [AuthController::class, 'devices']);
    });
});

Route::prefix('register')->group(function () {
    Route::get('/get-refer/{referCode}', [AuthController::class, 'getReferUser']);
    Route::get('/products', [AuthController::class, 'getProducts']);
    Route::get('/root-users', [AuthController::class, 'getUsers']);
    Route::post('/create-user', [AuthController::class, 'register']);
});





















// ======================
// Profile Routes
// ======================
use App\Http\Controllers\Auth\ProfileController;
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('profile')->group(function () {
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('/password', [ProfileController::class, 'changePassword']);
    });

    Route::prefix('users')->group(function () {
        Route::get('/', [ProfileController::class, 'getUsers']);
        Route::get('/get-all', [ProfileController::class, 'getAllUsers']);
        Route::get('/products', [ProfileController::class, 'getProducts']);
        Route::get('/root', [ProfileController::class, 'getRootUsers']);
        Route::post('/create', [ProfileController::class, 'createUser']);
        Route::post('/assign-tree', [ProfileController::class, 'assignTree']);
        Route::get('/get-ranking-users', [ProfileController::class, 'getRankingUsers']);
    });
});

// get tree user
Route::middleware(['auth:sanctum'])->group(function (){
    Route::get('/tree-user-log-root', [ProfileController::class, 'treeUserLogRoot']);
    Route::get('/tree-user', [ProfileController::class, 'treeUser']);
});
















// ======================
// Product Routes
// ======================
use App\Http\Controllers\Product\ProductController;
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('products')->group(function () {
        Route::post('/create', [ProductController::class, 'store']);
        Route::get('/', [ProductController::class, 'index']);

        Route::get('/get-categories', [ProductController::class, 'getCategory']);
        Route::get('/get-subcategories', [ProductController::class, 'getSubCategory']);
        Route::get('/get-brands', [ProductController::class, 'getBrand']);

        Route::post('/create-brand', [ProductController::class, 'storeBrand']);
        Route::delete('/delete-brand/{id}', [ProductController::class, 'deleteBrand']);
        Route::put('/edit-brand/{id}', [ProductController::class, 'editBrand']);

        Route::post('/create-category', [ProductController::class, 'storeCategory']);
        Route::delete('/delete-category/{id}', [ProductController::class, 'deleteCategory']);
        Route::put('/edit-category/{id}', [ProductController::class, 'editCategory']);

        Route::post('/create-sub-category', [ProductController::class, 'storeSubCategory']);
        Route::delete('/delete-sub-category/{id}', [ProductController::class, 'deleteSubCategory']);
        Route::put('/edit-sub-category/{id}', [ProductController::class, 'editSubCategory']);

        // Product sale report Route
        Route::get('/report', [ProductController::class, 'reportSale']);

        // LAST: dynamic route for product details, must be at the end of all product routes
        Route::post('/update/{id}', [ProductController::class, 'edit'])->where('id', '[0-9]+');
        Route::delete('/delete/{id}', [ProductController::class, 'delete'])->where('id', '[0-9]+');
        Route::get('/{slug}', [ProductController::class, 'show'])->where('slug', '[a-zA-Z0-9\-]+');
    });
});


















// ======================
// Customer Routes
// ======================
use App\Http\Controllers\Customer\CustomerController;
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('customer')->group(function (){

        Route::prefix('profile')->group(function () {
            Route::put('/', [CustomerController::class, 'update']);
            Route::put('/password', [CustomerController::class, 'changePassword']);
        });

        Route::prefix('users')->group(function () {
            Route::get('/', [CustomerController::class, 'getUsers']);
            Route::get('/auth', [CustomerController::class, 'getAuthUser']);
            Route::get('/root', [CustomerController::class, 'getRootUsers']);
            Route::post('/create', [CustomerController::class, 'createUser']);
            Route::get('/edit/{id}', [CustomerController::class, 'editUser']);
            Route::post('/update/{id}', [CustomerController::class, 'updateUser']);
            Route::post('/assign-tree', [CustomerController::class, 'assignTree']);
        });

        Route::prefix('orders')->group(function () {
            Route::get('/', [CustomerController::class, 'getOrders']);
            Route::post('/store', [CustomerController::class, 'storeOrder']);
        });

        Route::prefix('dashboard')->group(function() {
            Route::get('/', [CustomerController::class, 'dashboard']);
        });
    });
});













// ======================
// E-commerce Routes
// ======================
use App\Http\Controllers\Ecommerce\EcommerceProductController;
Route::prefix('public')->group(function () {

    Route::get('/products', [EcommerceProductController::class, 'index']);

    Route::get('/get-categories', [ProductController::class, 'getCategory']);
    Route::get('/get-subcategories', [ProductController::class, 'getSubCategory']);
    Route::get('/get-brands', [ProductController::class, 'getBrand']);
    Route::get('/product/{slug}', [ProductController::class, 'show'])->where('slug', '[a-zA-Z0-9\-]+');
});

use App\Http\Controllers\Ecommerce\CartController;
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::get('/{reg}', [CartController::class, 'getCartItem']);
        Route::post('/add-to-cart', [CartController::class, 'addToCart']);
        Route::post('/qty-update/{reg}/{product_id}/{variant_id}', [CartController::class, 'updateQty']);
        Route::post('/remove-to-cart/{cart_id}/{reg}/{product_id}/{variant_id}', [CartController::class, 'removeToCart']);
    });
});

use App\Http\Controllers\Payment\AccountController;
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('account')->group(function () {
        Route::get('/', [AccountController::class, 'index']);
        Route::get('/admin/statement', [AccountController::class, 'adminStatement']);
        Route::get('/admin/star/club', [AccountController::class, 'StarClubStatement']);
        Route::get('/admin/dynamic/club', [AccountController::class, 'DynamicClubStatement']);
    });
});












// =============================
// E-commerce Admin order Routes
// =============================
use App\Http\Controllers\Order\OrderController;
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/status', [OrderController::class, 'statusFilter']);
        Route::get('/{reg}', [OrderController::class, 'getOrderDetails']);
        Route::post('/update-status/{reg}', [OrderController::class, 'updateStatus']);
        Route::get('/customer/{user_id}', [OrderController::class, 'getCustomerDetails']);
        Route::post('/confirm/{reg}', [OrderController::class, 'confirmOrder']);

        Route::prefix('reports')->group(function(){
            Route::get('/sale', [OrderController::class, 'reportSale']);
            Route::get('/sale/filter', [OrderController::class, 'reportSaleFilter']);
        });
    });
});














// ======================
// Finance Routes
// ======================
use App\Http\Controllers\Finance\WalletController;
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('finance')->group(function () {
        Route::get('/', [ WalletController::class, 'index']);
        Route::get('/transaction', [ WalletController::class, 'transection']);
        Route::get('/admin/transaction', [ WalletController::class, 'processingTransection']);
        Route::post('/withdraw/store', [ WalletController::class, 'store']);
        Route::post('/withdraw/verify-otp', [ WalletController::class, 'verifyOtp']);
        Route::delete('/transaction/{id}', [ WalletController::class, 'transectionDelete']);
        Route::get('/transection-details/{transaction_id}/{user_id}', [WalletController::class, 'getTransaction']);
        Route::put('/update-status/{id}', [WalletController::class, 'updateStatus']);
    });
});




// ======================
// Notice Routes
// ======================
use App\Http\Controllers\Notice\NoticeController;
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('notice')->group(function () {
        Route::get('/', [NoticeController::class, 'index']);
        Route::get('/user', [NoticeController::class, 'userNotice']);
        Route::post('/create', [NoticeController::class, 'create']);
        // Route::get('/view/{file}', [NoticeController::class, 'attachView']);
        Route::delete('/delete/{id}', [NoticeController::class, 'delete']);
        Route::get('/view/{id}', [NoticeController::class, 'viewNotice']);
        Route::put('/update/{id}', [NoticeController::class, 'updateNotice']);
        // Route::get('/show-all-notices', [NoticeController::class, 'show']);
    });
});










// ======================
// Slider Routes
// ======================
use App\Http\Controllers\Ecommerce\SliderController;
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('slider')->group(function () {
        Route::get('/', [SliderController::class, 'index']);
        Route::post('/create', [SliderController::class, 'store']);
        Route::delete('/delete/{id}', [SliderController::class, 'delete']);
    });
});

Route::prefix('slider')->group(function () {
    Route::get('/public', [SliderController::class, 'show']);
});









// ======================
// Super Admin Routes
// ======================
use App\Http\Controllers\Admin\AdminController;
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('super-admin')->group(function () {
        Route::get('/', [ AdminController::class, 'index']);
        Route::get('/transaction', [ AdminController::class, 'transaction']);

        Route::get('/star-club/users', [AdminController::class, 'starClubUsers']);
        Route::post('/star-club/add-money/{user_id}', [AdminController::class, 'addMoneyStarClub']);

        Route::get('/dynamic-club/users', [AdminController::class, 'dynamicClubUsers']);
        Route::post('/dynamic-club/add-money/{user_id}', [AdminController::class, 'addMoneyDynamicClub']);

        Route::post('/add-money/{user_id}', [AdminController::class, 'addMoney']);
        Route::post('/deduct-money/{user_id}', [AdminController::class, 'deductMoney']);
        Route::get('/user/{user_id}', [AdminController::class, 'getUserDetails']);
        Route::put('/user/change-role', [AdminController::class, 'changeUserRole']);
    });
});

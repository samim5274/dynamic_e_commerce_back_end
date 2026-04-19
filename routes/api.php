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
        Route::get('/root', [ProfileController::class, 'getRootUsers']);
        Route::post('/create', [ProfileController::class, 'createUser']);
        Route::post('/assign-tree', [ProfileController::class, 'assignTree']);
    });
});

// get tree user
Route::get('/tree-user', [ProfileController::class, 'treeUser']);

















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
            Route::post('/assign-tree', [CustomerController::class, 'assignTree']);
        });

        Route::prefix('orders')->group(function () {
            Route::get('/', [CustomerController::class, 'getOrders']);
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
    Route::get('/{slug}', [ProductController::class, 'show'])->where('slug', '[a-zA-Z0-9\-]+');
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

// use App\Http\Controllers\Payment\PaymentController;
// Route::middleware('auth:sanctum')->group(function () {
//     Route::prefix('pay')->group(function () {

//     });
// });












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
    });
});

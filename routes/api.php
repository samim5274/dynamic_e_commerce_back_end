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
        // get routes
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
    });
});
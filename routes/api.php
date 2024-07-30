<?php

use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'menus'], function() {
    Route::get('/', [MenuController::class, 'index']);
    Route::post('/store', [MenuController::class, 'store']);
    Route::get('/search', [MenuController::class, 'search']); // Letakkan search di sini
    Route::get('/filter/{category}', [MenuController::class, 'filterByCategory']);
    Route::get('/Sfilter/{second_category}', [MenuController::class, 'filterBySecondCategory']);
    Route::get('/{id}', [MenuController::class, 'show']);
    Route::patch('/{id}', [MenuController::class, 'update']);
    Route::delete('/{id}', [MenuController::class, 'destroy']);
});

use App\Http\Controllers\CartController;

Route::prefix('carts')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/store', [CartController::class, 'store']);
    Route::get('/{id}', [CartController::class, 'show']);
    Route::put('/{id}', [CartController::class, 'update']);
    Route::delete('/{id}', [CartController::class, 'destroy']);
});

use App\Http\Controllers\OrderController;

Route::group(['prefix' => 'orders', 'middleware' => 'auth:sanctum'], function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/store', [OrderController::class, 'store']);
    Route::get('/{id}', [OrderController::class, 'show']);
    Route::put('/status/{id}', [OrderController::class, 'updateStatus']);
    Route::put('/price/{id}', [OrderController::class, 'updatePrice']);
    Route::get('/statistics', [OrderController::class, 'getSalesStatistics']);
});

use App\Http\Controllers\StoreStatusController;

Route::prefix('store-statuses')->group(function () {
    Route::get('/', [StoreStatusController::class, 'index']);
    Route::post('/store', [StoreStatusController::class, 'store']);
    Route::get('/{id}', [StoreStatusController::class, 'show']);
    Route::put('/{id}', [StoreStatusController::class, 'update']);
    Route::delete('/{id}', [StoreStatusController::class, 'destroy']);
});

use App\Http\Controllers\ToppingController;

Route::prefix('toppings')->group(function () {
    Route::get('/', [ToppingController::class, 'index']);
    Route::post('/store', [ToppingController::class, 'store']);
    Route::get('/{id}', [ToppingController::class, 'show']);
    Route::put('/{id}', [ToppingController::class, 'update']);
    Route::delete('/{id}', [ToppingController::class, 'destroy']);
});

Route::group(['prefix' => '/users'], function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/google-login', [UserController::class, 'googleLogin']);
    Route::get('/details', [UserController::class, 'details'])->middleware('auth:sanctum');
    Route::post('/update', [UserController::class, 'update'])->middleware('auth:sanctum');
    Route::post('/send-otp', [OtpController::class, 'sendOtp'])->middleware('auth:sanctum');
    Route::post('/verify-otp', [OtpController::class, 'verifyOtp'])->middleware('auth:sanctum');
    Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
    Route::delete('/{id}', [UserController::class, 'destroy']);
    Route::post('/updatePhone', [UserController::class, 'updatePhoneNumberForGoogle'])->middleware('auth:sanctum');;

});

use App\Http\Controllers\AdminController;

Route::group(['prefix' => '/admins'], function () {
    Route::post('/register', [AdminController::class, 'register']);
    Route::post('/login', [AdminController::class, 'login']);
    Route::put('/update', [AdminController::class, 'update'])->middleware('auth:sanctum');
    Route::post('/logout', [AdminController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/users', [AdminController::class, 'getUser']);  
    Route::put('/users/{id}/verify', [AdminController::class, 'verifyUser']);
    Route::put('/users/{id}/unverify', [AdminController::class, 'unverifyUser']);
});


use App\Http\Controllers\VariantController;

Route::prefix('variants')->group(function () {
    Route::get('/', [VariantController::class, 'index']);
    Route::post('/store', [VariantController::class, 'store']);
    Route::get('/{id}', [VariantController::class, 'show']);
    Route::put('/{id}', [VariantController::class, 'update']);
    Route::delete('/{id}', [VariantController::class, 'destroy']);
});

use App\Http\Controllers\HistoryController;

Route::prefix('history')->group(function () {
    Route::get('/', [HistoryController::class, 'index']);
    Route::post('/store', [HistoryController::class, 'store']);
    Route::get('/{id}', [HistoryController::class, 'show']);
    Route::put('/{id}', [HistoryController::class, 'update']);
    Route::delete('/{id}', [HistoryController::class, 'destroy']);
});

use App\Http\Controllers\PaymentController;
Route::group(['prefix' => '/payment', 'middleware' => 'auth:sanctum'], function() {
    Route::post('/create', [PaymentController::class, 'createPayment']);
    Route::get('/{id}', [PaymentController::class, 'getPaymentStatus']);
} );

use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\OrderDetailController;

Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('google-auth');
Route::get('/auth/google/call-back', [GoogleAuthController::class, 'handleGoogleCallback']);

Route::prefix('/order-details')->group(function () {
    Route::get('/', [OrderDetailController::class, 'index']);
    Route::post('/create', [OrderDetailController::class, 'createOrderDetail']);
    Route::get('/get', [OrderDetailController::class, 'getOrderDetails']);
    Route::put('/{id}', [OrderDetailController::class, 'update']);
    Route::delete('/{id}', [OrderDetailController::class, 'destroy']);
})->middleware('auth:sanctum');




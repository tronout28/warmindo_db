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
    Route::put('/disable/{id}', [MenuController::class, 'disableMenu']);
    Route::put('/enable/{id}', [MenuController::class, 'enableMenu']);
    Route::put('/{id}', [MenuController::class, 'update']);
    Route::delete('/{id}', [MenuController::class, 'destroy']);
});

use App\Http\Controllers\NotificationController;

Route::group(['prefix' => 'notifications', 'middleware' => ['auth:sanctum']], function () {
    Route::post('/send', [NotificationController::class, 'sendNotification']);
    Route::post('/send-to-all', [NotificationController::class, 'sendNotificationToAll']);
    Route::get('/all', [NotificationController::class, 'getNotifications']);
});

use App\Http\Controllers\CartController;

Route::group(['prefix' => 'carts', 'middleware' => 'auth:sanctum'], function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/store', [CartController::class, 'createCart']);
    Route::get('/showcarts', [CartController::class, 'getCart']);
    Route::put('/update/{id}', [CartController::class, 'updateCart']);
    Route::delete('/{id}', [CartController::class, 'destroy']);
});

use App\Http\Controllers\OrderController;

Route::group(['prefix' => 'orders', 'middleware' => 'auth:sanctum'], function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/store', [OrderController::class, 'store']);
    Route::get('/{id}', [OrderController::class, 'show']);
    Route::put('/price/{id}', [OrderController::class, 'updatePrice']);
    Route::get('/statistics', [OrderController::class, 'getSalesStatistics']);
    Route::put('/updatenote/{id}', [OrderController::class, 'updateNote']);
    Route::put('/updatepayment/{id}', [OrderController::class, 'updatepaymentmethod']);
    Route::post('/toHistory', [OrderController::class, 'tohistory']);
    Route::delete('/{id}', [OrderController::class, 'destroy']);
    Route::get('/filter/status', [OrderController::class, 'filterbystatues']);
    Route::post('/cancel/{id}', [OrderController::class, 'cancelOrder']);
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

    Route::post('/send/email', [OtpController::class, 'sendEmailOtp']);
    Route::post('/verify/email', [OtpController::class, 'verifyEmailOtp']);
    Route::post('/send-otp', [OtpController::class, 'sendOtp'])->middleware('auth:sanctum');
    Route::post('/verify-otp', [OtpController::class, 'verifyOtp'])->middleware('auth:sanctum');
    Route::post('/send-otp-phonenumber', [OtpController::class, 'sendOtpwithPhoneNumber']);

    Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/get-history', [UserController::class, 'getHistory'])->middleware('auth:sanctum');
    Route::delete('/{id}', [UserController::class, 'destroy']);
    Route::post('/forgot-password', [UserController::class, 'forgotPassword'])->middleware('auth:sanctum');
    Route::post('/updatePhone', [UserController::class, 'updatePhoneNumberForGoogle'])->middleware('auth:sanctum');;

});

use App\Http\Controllers\AdminController;

Route::group(['prefix' => '/admins'], function () {
    Route::post('/register', [AdminController::class, 'register']);
    Route::post('/login', [AdminController::class, 'login']);
    Route::get('/details', [AdminController::class, 'detailadmin'])->middleware('auth:sanctum');
    Route::put('/update', [AdminController::class, 'update'])->middleware('auth:sanctum');
    Route::post('/logout', [AdminController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/users', [AdminController::class, 'getUser']);  

    Route::post('/send/email', [OtpController::class, 'sendEmailOtp']);
    Route::post('/verify/email', [OtpController::class, 'verifyEmailOtp']);
    Route::post('/checkEmail', [AdminController::class, 'forgotPassword']);
    Route::post('/verify', [AdminController::class, 'verifyForgotPassword']);

    Route::put('/users/{id}/verify', [AdminController::class, 'verifyUser']);
    Route::put('/status/{id}', [OrderController::class, 'updateStatus']);
    Route::put('/users/{id}/unverify', [AdminController::class, 'unverifyUser']);
    Route::get('/orders', [AdminController::class, 'getOrders']);
    Route::get('/userdetailorder/{id}', [AdminController::class, 'userOrderdetail']); 
    Route:: get('/chart-sales', [OrderController::class, 'getChartOrder']);
    Route:: get('/chart-revenue', [OrderController::class, 'getChartRevenue']);
    Route::post('/rejectcancel/{id}', [AdminController::class, 'rejectcancel']);
    Route::post('/acceptcancel/{id}', [AdminController::class, 'acceptcancel']);
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
    Route::get('/', [HistoryController::class, 'getHistory']);
    Route::get('/detail/{id}', [HistoryController::class, 'show']);
    Route::delete('/{id}', [HistoryController::class, 'destroy']);
    Route::post('/tohistory', [HistoryController::class,'orderToHistory']);
});

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TransactionController;
Route::group(['prefix' => 'payments'], function () {
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/create', [PaymentController::class, 'createPayment']);
        Route::post('/update', [PaymentController::class, 'updatePaymentStatus']);
        Route::get('/get', [PaymentController::class, 'getInvoiceUser']);
        Route::delete('/expire', [PaymentController::class, 'expirePayment']);
    });

    Route::group(['prefix' => 'callback', 'middleware' => 'xendit.callback.token'], function () {
        Route::post('/invoice-status', [TransactionController::class, 'invoiceStatus']);
    });
});

Route::group(['prefix' => 'transaction', 'middleware' => ['auth:sanctum']], function () {
    Route::get('/get', [TransactionController::class, 'getTransaction']);
});

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

use App\Http\Controllers\RatingController;

Route::group(['prefix' => 'ratings', 'middleware' => 'auth:sanctum'], function () {
    Route::post('/rate', [RatingController::class, 'rateMenuItem']);
});




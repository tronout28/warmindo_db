<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\MenuController;

Route::prefix('menus')->group(function () {
    Route::get('/', [MenuController::class, 'index']);
    Route::post('/', [MenuController::class, 'store']);
    Route::get('/search', [MenuController::class, 'search']); // Letakkan search di sini
    Route::get('/filter/{category}', [MenuController::class, 'filterByCategory']); 
    Route::get('/{id}', [MenuController::class, 'show']);
    Route::patch('/{id}', [MenuController::class, 'update']);
    Route::delete('/{id}', [MenuController::class, 'destroy']);
});

use App\Http\Controllers\CartController;

Route::prefix('carts')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/', [CartController::class, 'store']);
    Route::get('/{id}', [CartController::class, 'show']);
    Route::put('/{id}', [CartController::class, 'update']);
    Route::delete('/{id}', [CartController::class, 'destroy']);
});


use App\Http\Controllers\OrderController;

Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::get('/{id}', [OrderController::class, 'show']);
    Route::put('/{id}', [OrderController::class, 'update']);
});

use App\Http\Controllers\StoreStatusController;

Route::prefix('store-statuses')->group(function () {
    Route::get('/', [StoreStatusController::class, 'index']);
    Route::post('/', [StoreStatusController::class, 'store']);
    Route::get('/{id}', [StoreStatusController::class, 'show']);
    Route::put('/{id}', [StoreStatusController::class, 'update']);
    Route::delete('/{id}', [StoreStatusController::class, 'destroy']);
});

use App\Http\Controllers\Api\ToppingController;

Route::prefix('toppings')->group(function () {
    Route::get('/', [ToppingController::class, 'index']);
    Route::post('/', [ToppingController::class, 'store']);
    Route::get('/{id}', [ToppingController::class, 'show']);
    Route::put('/{id}', [ToppingController::class, 'update']);
    Route::delete('/{id}', [ToppingController::class, 'destroy']);
});

Route::group(['prefix' => '/users'], function () {
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/login', [UserController::class, 'login']);
    Route::get('/details', [UserController::class, 'details'])->middleware('auth:sanctum');
});
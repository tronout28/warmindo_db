<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\MenuController;

Route::prefix('menus')->group(function () {
    Route::get('/', [MenuController::class, 'index']);
    Route::post('/', [MenuController::class, 'store']);
    Route::get('/{id}', [MenuController::class, 'show']);
    Route::patch('/{id}', [MenuController::class, 'update']);
    Route::delete('/{id}', [MenuController::class, 'destroy']);
    Route::get('/search/{name_menu}', [MenuController::class, 'search']); // Make sure this route is defined
    Route::get('/filter/{category}', [MenuController::class, 'filterByCategory']); // Make sure this route is defined
});

use App\Http\Controllers\OrderController;

Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::get('/{id}', [OrderController::class, 'show']);
    Route::put('/{id}', [OrderController::class, 'update']);
});



Route::group(['prefix' => '/users'], function () {
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/login', [UserController::class, 'login']);
    Route::get('/details', [UserController::class, 'details'])->middleware('auth:sanctum');
});
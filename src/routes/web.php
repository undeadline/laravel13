<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:sms');
Route::post('/verify-phone', [AuthController::class, 'verifyPhone']);
Route::post('/logout', [AuthController::class, 'logout']);

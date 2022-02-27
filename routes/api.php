<?php

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\Authentication\AuthenticationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('authentication')->group(function () {
    Route::post('login', [AuthenticationController::class, 'login'])->name('authentication.login');
    Route::post('register', [AuthenticationController::class, 'register'])->name('authentication.register');
    Route::post('password/forgot', [AuthenticationController::class, 'sendPasswordResetLinkEmail'])->name('authentication.password.forgot');
    Route::post('password/reset', [AuthenticationController::class, 'resetPassword'])->name('authentication.password.reset');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthenticationController::class, 'logout'])->name('authentication.logout');
        Route::get('me', [AuthenticationController::class, 'getAuthenticatedUser'])->name('authentication.me');

    });
});

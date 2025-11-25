<?php

use App\Http\Controllers\ApiAuthenticateControllers;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// == Rute Autentikasi ==
// Route::post('/register', [ApiAuthenticateControllers::class, 'register']); // Anda memiliki ini, tapi sepertinya diduplikasi di bawah
Route::controller(ApiAuthenticateControllers::class)->group(function () {
    Route::post('/register', 'register_views');
    Route::post('/login', 'login_views');
});



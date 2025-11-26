<?php

use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\ApiAuthenticateControllers;
use App\Http\Controllers\ApiCategoryController;
use App\Http\Controllers\ApiNewsController;
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
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::get('/users/get', 'showAll');
    Route::get('/users/{id}/role_editor', 'updateRoleToEditor');
});

// == Rute Category ==
Route::controller(ApiCategoryController::class)->group(function () {
    // READ
    Route::get('/category/get', 'showAllCategory');

    // CREATE (Menyimpan data baru)
    Route::post('/category/store', 'store');

    // UPDATE (Memperbarui data berdasarkan ID)
    Route::put('/category/{id}/update', 'update');
    // Anda juga bisa menggunakan PATCH jika ingin update parsial
    // Route::patch('/category/{id}/update', 'update');
});

// Route untuk CRUD Berita
Route::prefix('news')->controller(ApiNewsController::class)->group(function () {
    
    // Read: Akses publik (tidak perlu API Key)
    Route::get('/', 'index');        // GET /api/news
    Route::get('/{id}', 'show');     // GET /api/news/{id}
    
    // Write Operations: Memerlukan API Key
    Route::post('/news/', 'store');       // POST /api/news (Tambah Berita)
    Route::post('/news/{id}', 'update');  // POST /api/news/{id} (Update Berita, menggunakan POST untuk file upload)
    Route::delete('/news/{id}', 'destroy'); // DELETE /api/news/{id} (Hapus Berita)
});


// path get media
Route::get('/media/{path}', [MediaController::class, 'show'])->where('path', '.*');




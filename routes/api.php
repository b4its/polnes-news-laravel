<?php

use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\ApiAuthenticateControllers;
use App\Http\Controllers\ApiCategoryController;
use App\Http\Controllers\ApiCommentController;
use App\Http\Controllers\ApiNewsController;
use App\Http\Controllers\ApiNotificationController;
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
    Route::get('/news/category/get/{id}', 'newsInCategory');

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
    Route::get('/get/most_view/short', 'mostViewed_short');        // GET /api/news
    Route::get('/get/most_view/long', 'mostViewed_long');        // GET /api/news
    Route::get('/get/most_rated/short', 'mostRated_short');        // GET /api/news
    Route::get('/get/most_rated/long', 'mostRated_long');        // GET /api/news
    
    Route::get('/get/recent_news/first', 'recentNewsFirst');        // GET /api/news
    Route::get('/get/most_rated/first', 'mostRatedFirst');        // GET /api/news
    Route::get('/get/most_view/first', 'mostViewedFirst');        // GET /api/news
    Route::get('/{id}', 'show');     // GET /api/news/{id}
    
    // Write Operations: Memerlukan API Key
    Route::post('/post', 'store');       // POST /api/news (Tambah Berita)
    Route::post('/post/{id}', 'update');  // POST /api/{id} (Update Berita, menggunakan POST untuk file upload)
    Route::post('/add/views/{id}', 'addViews');  // POST /add/views/{id}
    Route::delete('/delete/{id}', 'destroy'); // DELETE /api/news/{id} (Hapus Berita)
});

// Grouping semua route terkait Komentar/Rating di bawah prefix 'comment'
Route::prefix('comment')->controller(ApiCommentController::class)->group(function () {
    
    /**
     * POST /api/comment/store/{newsId}
     * Menambahkan rating/komentar baru ke suatu berita.
     * Membutuhkan: X-Api-Key, userId, rating (di body request).
     */
    Route::post('/store/{newsId}', 'storeComment');
    
    /**
     * PATCH /api/comment/update/{newsId}
     * Memperbarui rating yang sudah ada oleh user tertentu pada berita tertentu.
     * Membutuhkan: X-Api-Key, userId, rating baru (di body request).
     */
    Route::patch('/update/{newsId}', 'updateComment');
    
    /**
     * GET /api/comment/get/{newsId}
     * Menampilkan semua rating/komentar untuk suatu berita.
     * Membutuhkan: X-Api-Key.
     */
    Route::get('/get/{newsId}', 'getComments');
});

Route::prefix('notification')->controller(ApiNotificationController::class)->group(function () {
    
    /**
     * GET /api/notification/
     * Menampilkan semua notifikasi, diurutkan berdasarkan created_at terbaru.
     * Membutuhkan: X-Api-Key.
     */
    Route::get('/get', 'getNotifications');
    Route::get('/get/general', 'getGeneralNotifications');
    Route::get('/news/get', 'getNewsRelatedNotifications');
    /**
     * POST /api/notification/store
     * Menyimpan notifikasi baru ke database.
     * Membutuhkan: X-Api-Key, title (body), newsId (body, opsional).
     */
    Route::post('/store', 'storeNotification');
});


// path get media
Route::get('/media/{path}', [MediaController::class, 'show'])->where('path', '.*');




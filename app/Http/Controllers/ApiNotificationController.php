<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\News;
use App\Models\Notification;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File; 

class ApiNotificationController extends Controller
{
    /**
     * Helper untuk validasi API Key dan Logging.
     */
    private function validateAndLogApiKey(Request $request)
    {
        $privateKey = config('app.private_api_key');
        $headerKey = $request->header('X-Api-Key'); 
        
        // Cek jika API Key tidak ada atau tidak cocok
        if (empty($headerKey) || $headerKey !== $privateKey) {
            Log::warning('API Key Mismatch: Unauthorized access attempt.');
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access: invalid API key'
            ], 401);
        }
        
        return null; // Lanjut jika valid
    }

    // ------------------------------------------------------------------
    // A. READ NOTIFIKASI UMUM (newsId = NULL)
    // ------------------------------------------------------------------
    public function getNotifications(Request $request)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Ambil data notifikasi
            // Eager load data news terkait, diurutkan berdasarkan created_at secara descending
            $notifications = Notification::with([
                // Pilih hanya kolom id, title, dan gambar dari news
                'news' => function ($query) {
                    $query->select('id', 'title', 'gambar'); 
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get(); 

            // Format respons data
            $formattedNotifications = $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'created_at' => $notification->created_at,
                    'news_id' => $notification->newsId,
                    'news_title' => $notification->news->title ?? null,
                    'news_image' => $notification->news->gambar ?? null,
                ];
            });


            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetched notifications list',
                'data' => $formattedNotifications
            ], 200);

        } catch (Exception $e) {
            Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error.'
            ], 500);
        }
    }
    /**
     * Menampilkan daftar notifikasi umum (newsId IS NULL), diurutkan berdasarkan created_at terbaru.
     * Memerlukan API Key.
     */
    public function getGeneralNotifications(Request $request)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Ambil data notifikasi di mana newsId adalah NULL
            $notifications = Notification::whereNull('newsId')
                ->orderBy('created_at', 'desc')
                ->get(); 

            // 3. Format respons data (hanya menggunakan kolom dari tabel notification)
            $formattedNotifications = $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => 'general_alert',
                    'title' => $notification->title,
                    'image' => $notification->gambar,
                    'created_at' => $notification->created_at->toDateTimeString(),
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetched general notifications',
                'data' => $formattedNotifications
            ], 200);

        } catch (Exception $e) {
            Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error.'
            ], 500);
        }
    }

    // ------------------------------------------------------------------
    // B. READ NOTIFIKASI BERITA (newsId IS NOT NULL)
    // ------------------------------------------------------------------

    /**
     * Menampilkan daftar notifikasi yang terkait berita (newsId IS NOT NULL),
     * diurutkan berdasarkan created_at terbaru, dengan detail berita dan Author.
     * Memerlukan API Key.
     */
    public function getNewsRelatedNotifications(Request $request)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Ambil data notifikasi di mana newsId IS NOT NULL
            // Eager load newsList dan author (nested relation)
            $notifications = Notification::whereNotNull('newsId')
                ->with([
                    // Menggunakan newsList sesuai nama relasi di Model Notification
                    'newsList' => function ($query) {
                        // Pilih kolom news yang dibutuhkan, termasuk authorId
                        $query->select('id', 'title', 'gambar', 'authorId'); 
                    },
                    // Load relasi author di dalam newsList
                    'newsList.author:id,name' 
                ])
                ->orderBy('created_at', 'desc')
                ->get(); 

            // 3. Format respons data (menggunakan data dari relasi newsList)
            $formattedNotifications = $notifications->map(function ($notification) {
                
                if ($notification->newsList) {
                    return [
                        'id' => $notification->id,
                        'type' => 'news_related',
                        'news_id' => $notification->newsId,
                        // Judul dan Gambar diambil dari data News terkait
                        'title' => $notification->newsList->title, 
                        'image' => $notification->newsList->gambar,
                        // Nama Author diambil dari relasi nested
                        'author_name' => $notification->newsList->author->name ?? 'Unknown Author', 
                        'created_at' => $notification->created_at->toDateTimeString(),
                    ];
                }
                // Jika data newsList hilang, kembalikan data dasar dari notifikasi
                return [
                    'id' => $notification->id,
                    'type' => 'news_related_missing',
                    'title' => $notification->title,
                    'news_id' => $notification->newsId,
                    'image' => $notification->gambar,
                    'author_name' => null,
                    'created_at' => $notification->created_at->toDateTimeString(),
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetched news related notifications',
                'data' => $formattedNotifications->filter()->values() 
            ], 200);

        } catch (Exception $e) {
            Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error.'
            ], 500);
        }
    }


    // ------------------------------------------------------------------
    // C. CREATE NOTIFIKASI
    // ------------------------------------------------------------------

    /**
     * Menyimpan notifikasi baru ke database.
     * Memerlukan API Key.
     * Request body: title (required), newsId (optional)
     */
    public function storeNotification(Request $request)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Validasi input
            $validator = Validator::make($request->all(), [
                'title'     => 'required|string|max:255',
                // newsId harus ada di tabel 'news' jika diberikan
                'newsId'    => 'nullable|integer|exists:news,id', 
                // Opsional: jika ini notifikasi umum, bisa ada gambar terpisah
                'gambar'    => 'nullable|text', 
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            
            $gambarPath = $request->gambar; 

            // Jika newsId diberikan, ambil gambar dari data berita terkait
            if ($request->newsId) {
                $news = News::find($request->newsId);
                if ($news) {
                    $gambarPath = $news->gambar; // Ganti gambar notifikasi dengan gambar berita
                }
            }

            // 3. Buat notifikasi baru
            $notification = Notification::create([
                'title'     => $request->title,
                'newsId'    => $request->newsId,
                'gambar'    => $gambarPath,
            ]);

            return response()->json(['status' => 'success', 'message' => 'Notification created successfully', 'data' => $notification], 201);

        } catch (QueryException $e) { 
            Log::error('Database Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Database error: Could not create notification.'], 500);

        } catch (Exception $e) {
            Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Internal server error.'], 500);
        }
    }
}
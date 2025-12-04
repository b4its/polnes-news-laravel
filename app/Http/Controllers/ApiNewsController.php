<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ApiNewsController extends Controller
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
    // A. READ (Index & Show) - Memerlukan API Key
    // ------------------------------------------------------------------

    /**
     * Menampilkan semua daftar berita (dengan Paginasi).
     * Memerlukan API Key.
     */
    public function index(Request $request)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Ambil data berita dengan PAGINASI (Default 10 item per halaman)
            $news = News::with(['author:id,name', 'category:id,name'])
                        ->orderBy('created_at', 'desc')
                        // 💡 PERBAIKAN: Menggunakan paginate() untuk mengembalikan objek PaginatedData
                        ->paginate(5); 

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetched news list',
                'data' => $news // $news sekarang adalah objek PaginatedData
            ], 200);

        } catch (Exception $e) {
            Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error.'
            ], 500);
        }
    }
    
        public function recentNewsFirst(Request $request)
        {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Ambil data berita dengan PAGINASI (Default 10 item per halaman)
            $news = News::with(['author:id,name', 'category:id,name'])
                        ->latest() // Menggantikan orderBy('created_at', 'desc')
                        ->first();  // Mengambil data pertama (data tunggal) dari hasil yang diurutkan

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetched news list',
                'data' => $news // $news sekarang adalah objek PaginatedData
            ], 200);

        } catch (Exception $e) {
            Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error.'
            ], 500);
        }
    }

    public function mostViewedFirst(Request $request)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Ambil data berita dengan PAGINASI
            $news = News::with(['author:id,name', 'category:id,name'])
                        ->orderBy('views', 'desc') 
                        // 💡 PERBAIKAN: Menggunakan paginate() untuk mengembalikan objek PaginatedData
                        ->first();

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetched news list',
                'data' => $news
            ], 200);

        } catch (Exception $e) {
            Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error.'
            ], 500);
        }
    }

public function mostRatedFirst(Request $request)
{
    try {
        // 1. Cek API Key
        if ($response = $this->validateAndLogApiKey($request)) {
            return $response;
        }

        // 2. Hitung Rata-rata Rating dan Urutkan
        $news = News::with(['author:id,name', 'category:id,name'])
            ->leftJoin(
                DB::raw('(SELECT newsId, AVG(rating) as average_rating FROM comment GROUP BY newsId) as ratings'),
                'news.id', '=', 'ratings.newsId'
            )
            ->orderByDesc('ratings.average_rating')
            ->orderBy('news.created_at', 'desc')
            ->select('news.*', 'ratings.average_rating')
            // 💡 PERBAIKAN: Menggunakan first() untuk mendapatkan data tunggal (rekaman pertama)
            ->first(); 
            
        // 3. Menyesuaikan response jika data tidak ditemukan
        if (!$news) {
            return response()->json([
                'status' => 'not found',
                'message' => 'No news found with ratings.',
                'data' => null
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            // 💡 Mengganti pesan menjadi 'first most rated news' karena hanya 1 data
            'message' => 'Successfully fetched the single most rated news.', 
            'data' => $news
        ], 200);

    } catch (Exception $e) {
        // Log Error
        Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
        
        return response()->json([
            'status' => 'error',
            'message' => 'Internal server error.'
        ], 500);
    }
}
    
    

    /**
     * Menampilkan berita paling banyak dilihat (pendek/short).
     * TIDAK menggunakan paginasi (mengembalikan Array langsung).
     */
    public function mostViewed_short(Request $request)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Ambil data berita
            $news = News::with(['author:id,name', 'category:id,name'])
                        ->orderBy('views', 'desc')
                        ->take(5) 
                        ->get(); 

            // 💡 CATATAN: Response ini akan mengembalikan "data": [Array]
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetched news list',
                'data' => $news
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
     * Menampilkan berita paling banyak dilihat (panjang/long) dengan Paginasi.
     * Memerlukan API Key.
     */
    public function mostViewed_long(Request $request)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Ambil data berita dengan PAGINASI
            $news = News::with(['author:id,name', 'category:id,name'])
                        ->orderBy('views', 'desc') 
                        // 💡 PERBAIKAN: Menggunakan paginate() untuk mengembalikan objek PaginatedData
                        ->paginate(5); 

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetched news list',
                'data' => $news
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
     * Menampilkan berita paling banyak di-rating (panjang/long) dengan Paginasi.
     * Memerlukan API Key.
     */
    public function mostRated_long(Request $request)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Hitung Rata-rata Rating dan Urutkan
            $news = News::with(['author:id,name', 'category:id,name'])
                        ->leftJoin(
                            DB::raw('(SELECT newsId, AVG(rating) as average_rating FROM comment GROUP BY newsId) as ratings'),
                            'news.id', '=', 'ratings.newsId'
                        )
                        ->orderByDesc('ratings.average_rating')
                        ->orderBy('news.created_at', 'desc')
                        ->select('news.*', 'ratings.average_rating')
                        // 💡 PERBAIKAN: Menggunakan paginate() untuk mengembalikan objek PaginatedData
                        ->paginate(5);

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetched most rated news list',
                'data' => $news
            ], 200);

        } catch (Exception $e) {
            // Log Error
            Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error.'
            ], 500);
        }
    }
    
    
    public function mostRated_short(Request $request)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Hitung Rata-rata Rating dan Urutkan
            $news = News::with(['author:id,name', 'category:id,name'])
                        ->leftJoin(
                            DB::raw('(SELECT newsId, AVG(rating) as average_rating FROM comment GROUP BY newsId) as ratings'),
                            'news.id', '=', 'ratings.newsId'
                        )
                        ->orderByDesc('ratings.average_rating')
                        ->orderBy('news.created_at', 'desc')
                        ->select('news.*', 'ratings.average_rating')
                        ->take(5)
                        ->get();

            // 💡 CATATAN: Response ini akan mengembalikan "data": [Array]
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetched most rated news list',
                'data' => $news
            ], 200);

        } catch (Exception $e) {
            // Log Error
            Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error.'
            ], 500);
        }
    }
    
    /**
     * Menampilkan detail berita berdasarkan ID.
     * Memerlukan API Key.
     */
    public function show(Request $request, $id)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }
            
            // 2. Cari berita
            $news = News::with(['author:id,name', 'category:id,name'])->find($id);

            if (!$news) {
                return response()->json(['status' => 'error', 'message' => 'News not found'], 404);
            }

            // 3. Tingkatkan jumlah views
            $news->increment('views');

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetched news detail',
                'data' => $news // Mengembalikan satu objek
            ], 200);

        } catch (Exception $e) {
            Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Internal server error.'], 500);
        }
    }


    // ------------------------------------------------------------------
    // B. CREATE (Store) - Memerlukan API Key
    // ------------------------------------------------------------------

    /**
     * Menyimpan berita baru ke database.
     * Memerlukan API Key.
     */
    public function store(Request $request)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Validasi input
            // 💡 PERBAIKAN: Kunci 'content' dan 'contents'
            $validator = Validator::make($request->all(), [
                'title'       => 'required|string|max:255',
                'categoryId'  => 'nullable|integer|exists:category,id',
                'gambar'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
                'contents'    => 'required|string', // ✅ Menggunakan contents
                'linkYoutube' => 'nullable|string',
                'authorId'    => 'required|integer|exists:users,id',
                'status'      => 'nullable|string|in:draft,published' 
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $imagePath = null;
            if ($request->hasFile('gambar')) {
                $image = $request->file('gambar');
                // Peningkatan keamanan path file
                $imageName = time() . '-' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/news'), $imageName);
                $imagePath = 'images/news/' . $imageName;
            }

            // 3. Buat berita baru
            $news = News::create([
                'title'       => $request->title,
                'categoryId'  => $request->categoryId,
                'gambar'      => $imagePath,
                'contents'    => $request->contents, // ✅ Menggunakan $request->contents
                'authorId'    => $request->authorId,
                'views'       => 0, 
                'linkYoutube' => $request->linkYoutube,
                'status'      => $request->status ?? 'draft',
            ]);

            return response()->json(['status' => 'success', 'message' => 'News created successfully', 'data' => $news], 201);

        } catch (QueryException $e) { 
            Log::error('Database Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Database error: Could not create news.'], 500);

        } catch (Exception $e) {
            Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Internal server error.'], 500);
        }
    }


    // ------------------------------------------------------------------
    // C. UPDATE - Memerlukan API Key
    // ------------------------------------------------------------------

    public function addViews(int $idNews)
    {
        try {
            // 1. Cari berita
            // Gunakan findOrFail agar langsung melempar 404 jika tidak ditemukan
            $news = News::findOrFail($idNews);

            // 2. Tambahkan nilai 'views' sebanyak 1
            // Metode increment() aman dari kondisi race dan sangat efisien
            $news->increment('views');

            // 3. Ambil data views yang baru untuk respons
            $updatedNews = News::find($idNews);
            $newViews = $updatedNews->views;

            Log::info("Views for News ID {$idNews} incremented. New views: {$newViews}");

            return response()->json([
                'status' => 'success',
                'message' => 'Views incremented successfully.',
                'newsId' => $idNews,
                'newViews' => $newViews
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Jika berita tidak ditemukan
            return response()->json(['status' => 'error', 'message' => 'News not found'], 404);

        } catch (Exception $e) {
            // Error umum lainnya
            Log::error('Error in addViews: ' . $e->getMessage(), ['idNews' => $idNews]);
            return response()->json(['status' => 'error', 'message' => 'Internal server error.'], 500);
        }
    }


    /**
     * Memperbarui berita di database.
     * Memerlukan API Key.
     */
    public function update(Request $request, $id)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Cari berita
            $news = News::find($id);
            if (!$news) {
                return response()->json(['status' => 'error', 'message' => 'News not found'], 404);
            }
            
            // 3. Validasi input
            // 💡 PERBAIKAN: Kunci 'content' dan 'contents'
            $validator = Validator::make($request->all(), [
                'title'       => 'required|string|max:255',
                'categoryId'  => 'nullable|integer|exists:category,id',
                'gambar'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
                'contents'    => 'required|string', // ✅ Menggunakan contents
                'linkYoutube' => 'nullable|string',
                'authorId'    => 'nullable|integer|exists:users,id',
                'status'      => 'nullable|string|in:draft,published'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $imagePath = $news->gambar; 
            
            // 4. Proses update gambar (jika ada file baru)
            if ($request->hasFile('gambar')) {
                // Hapus gambar lama
                if ($news->gambar && File::exists(public_path($news->gambar))) {
                    File::delete(public_path($news->gambar));
                }

                $image = $request->file('gambar');
                // Peningkatan keamanan path file
                $imageName = time() . '-' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/news'), $imageName);
                $imagePath = 'images/news/' . $imageName;
            }

            // 5. Perbarui data berita
            $news->update([
                'title'       => $request->title,
                'categoryId'  => $request->categoryId,
                'gambar'      => $imagePath,
                'contents'    => $request->contents, // ✅ Menggunakan $request->contents
                'authorId'    => $request->authorId ?? $news->authorId,
                'linkYoutube' => $request->linkYoutube,
                'status'      => $request->status ?? $news->status,
            ]);

            return response()->json(['status' => 'success', 'message' => 'News updated successfully', 'data' => $news], 200);

        } catch (Exception $e) {
            Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Internal server error.'], 500);
        }
    }




    // ------------------------------------------------------------------
    // D. DELETE (Destroy) - Memerlukan API Key
    // ------------------------------------------------------------------

    /**
     * Menghapus berita dari database.
     * Memerlukan API Key.
     */
    public function destroy(Request $request, $id)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Cari berita
            $news = News::find($id);
            if (!$news) {
                return response()->json(['status' => 'error', 'message' => 'News not found'], 404);
            }

            // 3. Hapus file gambar
            if ($news->gambar && File::exists(public_path($news->gambar))) {
                File::delete(public_path($news->gambar));
            }
            
            // 4. Hapus data berita
            $news->delete();

            return response()->json(['status' => 'success', 'message' => 'News deleted successfully'], 200);

        } catch (Exception $e) {
            Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Internal server error.'], 500);
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
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

public function dashboardCountAdmin(Request $request)
{
    try {
        // 1. Opsional: Cek API Key (disesuaikan dengan logic di fungsi show)
        // Jika Anda ingin menerapkan validasi API key di sini juga:
        if ($response = $this->validateAndLogApiKey($request)) {
            return $response;
        }

        // 2. Hitung total record dengan status "PENDING_REVIEW"
        // Menggunakan metode 'where' untuk filter dan 'count' untuk menghitung.
        $totalPublished = News::where('status', 'PUBLISHED')->count();
        $totalRejected = News::where('status', 'REJECTED')->count();
        $totalReview = News::where('status', 'PENDING_REVIEW')->count();
        $totalViews = News::sum('views');
        $totalReaders = User::where('role', 'USER')->count();
        $totalCategories = Category::count();

        // 3. Kembalikan respons JSON
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully fetched count of pending review news',
            'data' => [
                'total_pending_review' => $totalReview,
                'total_published' => $totalPublished,
                'total_rejected' => $totalRejected,
                'total_views' => $totalViews,
                'total_readers' => $totalReaders,
                'total_categories' => $totalCategories
            ]
        ], 200);

    } catch (Exception $e) {
        Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
        return response()->json(['status' => 'error', 'message' => 'Internal server error.'], 500);
    }
}



    public function newsPublished(Request $request)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Ambil data berita dengan PAGINASI (Default 10 item per halaman)
            $news = News::with(['author:id,name', 'category:id,name'])
                        ->where('status', 'PUBLISHED') 
                        ->orderBy('created_at', 'desc')
                        // 💡 Menggunakan paginate() untuk mengembalikan objek PaginatedData
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


    public function newsDraft(Request $request)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Ambil data berita dengan PAGINASI (Default 10 item per halaman)
            $news = News::with(['author:id,name', 'category:id,name'])
                        ->where('status', 'DRAFT') 
                        ->orderBy('created_at', 'desc')
                        // 💡 Menggunakan paginate() untuk mengembalikan objek PaginatedData
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

    public function newsReview(Request $request)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Ambil data berita dengan PAGINASI (Default 10 item per halaman)
            $news = News::with(['author:id,name', 'category:id,name'])
                        ->where('status', 'PENDING_REVIEW') 
                        ->orderBy('created_at', 'desc')
                        // 💡 Menggunakan paginate() untuk mengembalikan objek PaginatedData
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
                        ->where('status', 'PUBLISHED')
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
                        ->where('status', 'PUBLISHED')
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
            ->where('status', 'PUBLISHED')
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
                        ->where('status', 'PUBLISHED')
                        ->paginate(5); 

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
                        ->where('status', 'PUBLISHED')
                        ->orderByDesc('ratings.average_rating')
                        ->orderBy('news.created_at', 'desc')
                        ->select('news.*', 'ratings.average_rating')
                        ->paginate(5);

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
            $validator = Validator::make($request->all(), [
                'title'       => 'required|string|max:255',
                'categoryId'  => 'nullable|integer|exists:category,id',
                'gambar'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
                'thumbnail'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // <--- TAMBAHKAN VALIDASI INI
                'contents'    => 'required|string',
                'linkYoutube' => 'nullable|string',
                'authorId'    => 'required|integer|exists:users,id',
                'status'      => 'nullable|string|in:DRAFT,PUBLISHED,REJECTED' 
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            // --- PROSES GAMBAR UTAMA ---
            $imagePath = null;
            if ($request->hasFile('gambar')) {
                $image = $request->file('gambar');
                $imageName = time() . '-' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                // Simpan ke folder yang sama dengan logika Anda sebelumnya
                $image->move(public_path('media/gambar/temp'), $imageName);
                $imagePath = 'media/gambar/temp/' . $imageName;
            }

            // --- PROSES THUMBNAIL (BARU) ---
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                $thumb = $request->file('thumbnail');
                // Tambahkan prefix 'thumb-' agar nama file unik
                $thumbName = time() . '-thumb-' . Str::random(10) . '.' . $thumb->getClientOriginalExtension();
                
                // Simpan ke folder yang sama (sesuaikan folder jika Anda ingin beda)
                $thumb->move(public_path('media/gambar/temp'), $thumbName);
                $thumbnailPath = 'media/gambar/temp/' . $thumbName;
            }

            // 3. Buat berita baru
            $news = News::create([
                'title'       => $request->title,
                'categoryId'  => $request->categoryId,
                'gambar'      => $imagePath,
                'thumbnail'   => $thumbnailPath, // <--- MASUKKAN KE DATABASE
                'contents'    => $request->contents,
                'authorId'    => $request->authorId,
                'views'       => 1, 
                'linkYoutube' => $request->linkYoutube,
                'status'      => $request->status ?? 'DRAFT',
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



    
public function storeAdmin(Request $request)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Validasi input
            $validator = Validator::make($request->all(), [
                'title'       => 'required|string|max:255',
                'categoryId'  => 'nullable|integer|exists:category,id',
                'gambar'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
                'thumbnail'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // <--- TAMBAHKAN VALIDASI INI
                'contents'    => 'required|string',
                'linkYoutube' => 'nullable|string',
                'authorId'    => 'required|integer|exists:users,id',
                'status'      => 'nullable|string|in:DRAFT,PUBLISHED,REJECTED' 
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            // --- PROSES GAMBAR UTAMA ---
            $imagePath = null;
            if ($request->hasFile('gambar')) {
                $image = $request->file('gambar');
                $imageName = time() . '-' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                // Simpan ke folder yang sama dengan logika Anda sebelumnya
                $image->move(public_path('media/gambar/temp'), $imageName);
                $imagePath = 'media/gambar/temp/' . $imageName;
            }

            // --- PROSES THUMBNAIL (BARU) ---
            $thumbnailPath = null;
            if ($request->hasFile('thumbnail')) {
                $thumb = $request->file('thumbnail');
                // Tambahkan prefix 'thumb-' agar nama file unik
                $thumbName = time() . '-thumb-' . Str::random(10) . '.' . $thumb->getClientOriginalExtension();
                
                // Simpan ke folder yang sama (sesuaikan folder jika Anda ingin beda)
                $thumb->move(public_path('media/gambar/temp'), $thumbName);
                $thumbnailPath = 'media/gambar/temp/' . $thumbName;
            }

            // 3. Buat berita baru
            $news = News::create([
                'title'       => $request->title,
                'categoryId'  => $request->categoryId,
                'gambar'      => $imagePath,
                'thumbnail'   => $thumbnailPath, // <--- MASUKKAN KE DATABASE
                'contents'    => $request->contents,
                'authorId'    => $request->authorId,
                'views'       => 1, 
                'linkYoutube' => $request->linkYoutube,
                'status'      => $request->status ?? 'PUBLISHED',
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

public function updatePublishStatus(int $idNews)
    {
        try {
            // 1. Cari berita
            // Gunakan findOrFail agar langsung melempar 404 jika tidak ditemukan
            $news = News::findOrFail($idNews);

            // 2. Perbarui nilai kolom 'status' menjadi 'PUBLISHED'
            $news->status = 'PUBLISHED';
            $news->save(); // Simpan perubahan ke database

            Log::info("News ID {$idNews} status updated to PUBLISHED.");

            return response()->json([
                'status' => 'success',
                'message' => 'News successfully published.',
                'newsId' => $idNews,
                'newStatus' => 'PUBLISHED'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Jika berita tidak ditemukan
            return response()->json(['status' => 'error', 'message' => 'News not found'], 404);

        } catch (Exception $e) {
            // Error umum lainnya
            Log::error('Error in publishNews: ' . $e->getMessage(), ['idNews' => $idNews]);
            return response()->json(['status' => 'error', 'message' => 'Internal server error.'], 500);
        }
    }

public function updateDraftStatus(int $idNews)
    {
        try {
            // 1. Cari berita
            // Gunakan findOrFail agar langsung melempar 404 jika tidak ditemukan
            $news = News::findOrFail($idNews);

            // 2. Perbarui nilai kolom 'status' menjadi 'DRAFT'
            $news->status = 'DRAFT';
            $news->save(); // Simpan perubahan ke database

            Log::info("News ID {$idNews} status updated to DRAFT.");

            return response()->json([
                'status' => 'success',
                'message' => 'News successfully draft.',
                'newsId' => $idNews,
                'newStatus' => 'DRAFT'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Jika berita tidak ditemukan
            return response()->json(['status' => 'error', 'message' => 'News not found'], 404);

        } catch (Exception $e) {
            // Error umum lainnya
            Log::error('Error in publishNews: ' . $e->getMessage(), ['idNews' => $idNews]);
            return response()->json(['status' => 'error', 'message' => 'Internal server error.'], 500);
        }
    }

public function updateReviewStatus(int $idNews)
    {
        try {
            // 1. Cari berita
            // Gunakan findOrFail agar langsung melempar 404 jika tidak ditemukan
            $news = News::findOrFail($idNews);

            // 2. Perbarui nilai kolom 'status' menjadi 'PENDING_REVIEW'
            $news->status = 'PENDING_REVIEW';
            $news->save(); // Simpan perubahan ke database

            Log::info("News ID {$idNews} status updated to Pending Review.");

            return response()->json([
                'status' => 'success',
                'message' => 'News successfully Pending Review.',
                'newsId' => $idNews,
                'newStatus' => 'PENDING_REVIEW'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Jika berita tidak ditemukan
            return response()->json(['status' => 'error', 'message' => 'News not found'], 404);

        } catch (Exception $e) {
            // Error umum lainnya
            Log::error('Error in publishNews: ' . $e->getMessage(), ['idNews' => $idNews]);
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
            $validator = Validator::make($request->all(), [
                'title'       => 'required|string|max:255',
                'categoryId'  => 'nullable|integer|exists:category,id',
                'gambar'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
                'thumbnail'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
                'contents'    => 'required|string',
                'linkYoutube' => 'nullable|string',
                'authorId'    => 'nullable|integer|exists:users,id',
                'status'      => 'nullable|string|in:DRAFT,published' // Pastikan ini konsisten lowercase/uppercase di DB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Validation failed', 
                    'errors' => $validator->errors()
                ], 422);
            }

            // Data yang akan diupdate
            $dataToUpdate = [
                'title'       => $request->title,
                'categoryId'  => $request->categoryId,
                'contents'    => $request->contents,
                'linkYoutube' => $request->linkYoutube,
                'status'      => $request->status ?? $news->status,
            ];

            if ($request->has('authorId')) {
                $dataToUpdate['authorId'] = $request->authorId;
            }

            // 4. Proses Update Gambar UTAMA
            if ($request->hasFile('gambar')) {
                // Hapus gambar lama
                if ($news->gambar && File::exists(public_path($news->gambar))) {
                    File::delete(public_path($news->gambar));
                }

                $image = $request->file('gambar');
                $imageName = time() . '-' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/news'), $imageName);
                
                $dataToUpdate['gambar'] = 'images/news/' . $imageName;
            }

            // ============================================================
            // 5. Proses Update THUMBNAIL (INI YANG KURANG SEBELUMNYA)
            // ============================================================
            if ($request->hasFile('thumbnail')) { // <--- TAMBAHKAN BLOK INI
                // Hapus thumbnail lama jika ada
                if ($news->thumbnail && File::exists(public_path($news->thumbnail))) {
                    File::delete(public_path($news->thumbnail));
                }

                $thumb = $request->file('thumbnail');
                // Beri nama unik, misal tambahkan prefix 'thumb-'
                $thumbName = time() . '-thumb-' . Str::random(10) . '.' . $thumb->getClientOriginalExtension();
                
                // Simpan. Bisa di folder sama atau beda. Contoh di sini folder sama 'images/news'
                $thumb->move(public_path('images/news'), $thumbName);
                
                // Masukkan ke array update
                $dataToUpdate['thumbnail'] = 'images/news/' . $thumbName;
            }
            // ============================================================

            // 6. Eksekusi Update
            $news->update($dataToUpdate);

            return response()->json([
                'status' => 'success', 
                'message' => 'News updated successfully', 
                'data' => $news
            ], 200);

        } catch (QueryException $e) {
            Log::error('Database Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Database error during update.'], 500);

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
<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
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
     * Menampilkan semua daftar berita.
     * Memerlukan API Key.
     */
    public function index(Request $request)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Ambil data berita
            $news = News::with(['author:id,name', 'category:id,name'])
                        ->orderBy('created_at', 'desc')
                        ->paginate(10); 

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
                'data' => $news
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
                'title'      => 'required|string|max:255',
                'categoryId' => 'nullable|integer|exists:category,id',
                'gambar'     => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
                'content'    => 'required|string', // Kunci 'content'
                'linkYoutube' => 'nullable|string',
                'authorId'   => 'required|integer|exists:users,id',
                'status'     => 'nullable|string|in:draft,published' 
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $imagePath = null;
            if ($request->hasFile('gambar')) {
                $image = $request->file('gambar');
                $imageName = time() . '-' . Str::slug($request->title) . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/news'), $imageName);
                $imagePath = 'images/news/' . $imageName;
            }

            // 3. Buat berita baru
            $news = News::create([
                'title'       => $request->title,
                'categoryId'  => $request->categoryId,
                'gambar'      => $imagePath,
                'content'     => $request->contents, // Menggunakan $request->content
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
                'title'      => 'required|string|max:255',
                'categoryId' => 'nullable|integer|exists:category,id',
                'gambar'     => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
                'content'    => 'required|string', // Kunci 'content'
                'linkYoutube' => 'nullable|string',
                'authorId'   => 'nullable|integer|exists:users,id',
                'status'     => 'nullable|string|in:draft,published'
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
                $imageName = time() . '-' . Str::slug($request->title) . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/news'), $imageName);
                $imagePath = 'images/news/' . $imageName;
            }

            // 5. Perbarui data berita
            $news->update([
                'title'       => $request->title,
                'categoryId'  => $request->categoryId,
                'gambar'      => $imagePath,
                'content'     => $request->contents, // Menggunakan $request->content
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
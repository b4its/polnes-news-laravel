<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\News;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ApiCommentController extends Controller
{
    /**
     * Memvalidasi API Key dari header permintaan.
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

    /**
     * Menyimpan rating baru untuk berita.
     */
    public function storeComment(Request $request, $newsId)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Cek apakah berita ada
            $news = News::find($newsId);
            if (!$news) {
                return response()->json(['status' => 'error', 'message' => 'News not found'], 404);
            }

            // 3. Validasi input: userId harus ada dan rating harus 1-5
            $validator = Validator::make($request->all(), [
                'userId' => 'required|integer|exists:users,id',
                'rating' => 'required|integer|min:1|max:5',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Opsional: Cek apakah user sudah pernah memberi rating pada berita ini (kode ini dinonaktifkan untuk memungkinkan multiple rating, aktifkan jika hanya boleh 1x)
           
            $existingComment = Comment::where('userId', $request->userId)
                                          ->where('newsId', $newsId)
                                          ->first();
            if ($existingComment) {
                return response()->json(['status' => 'error', 'message' => 'User has already rated this news'], 409);
            }
     

            // 4. Simpan ke tabel comment
            $comment = Comment::create([
                'userId' => $request->userId,
                'newsId' => $newsId, // Mengambil dari parameter URL
                'rating' => $request->rating,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Rating added successfully',
                'data' => $comment
            ], 201);

        } catch (QueryException $e) {
            Log::error('Database Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Database error: Could not save rating.'], 500);

        } catch (Exception $e) {
            Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Internal server error.'], 500);
        }
    }

    /**
     * Memperbarui rating/komentar yang sudah ada berdasarkan userId dan newsId.
     * Menggunakan PUT/PATCH.
     */
    public function updateComment(Request $request, $newsId)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Cek apakah berita ada
            $news = News::find($newsId);
            if (!$news) {
                return response()->json(['status' => 'error', 'message' => 'News not found'], 404);
            }

            // 3. Validasi input
            $validator = Validator::make($request->all(), [
                // userId diperlukan untuk mencari komentar yang akan diupdate
                'userId' => 'required|integer|exists:users,id',
                'rating' => 'required|integer|min:1|max:5',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // 4. Cari komentar yang sudah ada berdasarkan userId dan newsId
            $comment = Comment::where('userId', $request->userId)
                                ->where('newsId', $newsId)
                                ->first();

            // 5. Cek apakah komentar ditemukan
            if (!$comment) {
                // Jika tidak ada, kembalikan 404, karena ini adalah operasi UPDATE
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rating by this user for this news not found. Use POST to create a new one.'
                ], 404);
            }

            // 6. Update rating
            $comment->rating = $request->rating;
            $comment->save();

            // 7. Response sukses
            return response()->json([
                'status' => 'success',
                'message' => 'Rating updated successfully',
                'data' => $comment
            ], 200);

        } catch (QueryException $e) {
            Log::error('Database Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Database error: Could not update rating.'], 500);

        } catch (Exception $e) {
            Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Internal server error.'], 500);
        }
    }


    /**
     * Menampilkan daftar komentar/rating berdasarkan ID Berita.
     * Termasuk data User (nama) yang memberikan rating.
     */
    public function getComments(Request $request, $newsId)
    {
        try {
            // 1. Cek API Key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Cek apakah berita ada (opsional, bisa langsung query comment jika ingin lebih cepat)
            if (!News::where('id', $newsId)->exists()) {
                return response()->json(['status' => 'error', 'message' => 'News not found'], 404);
            }

            // 3. Ambil data comment dengan relasi user
            // Menggunakan with('user:id,name') agar hanya mengambil kolom id dan name dari tabel users
            $comments = Comment::with('user:id,name')
                                 ->where('newsId', $newsId)
                                 ->orderBy('created_at', 'desc') // Urutkan dari yang terbaru
                                 ->get();

            // Hitung rata-rata rating untuk berita ini (opsional, tapi berguna)
            $averageRating = $comments->avg('rating');

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetched comments/ratings',
                'meta' => [
                    'total_ratings' => $comments->count(),
                    'average_rating' => round($averageRating, 1) // Membulatkan 1 desimal
                ],
                'data' => $comments
            ], 200);

        } catch (Exception $e) {
            Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Internal server error.'], 500);
        }
    }
}
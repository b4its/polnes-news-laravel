<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
class ApiCategoryController extends Controller
{
    //
    private function validateAndLogApiKey(Request $request)
    {
        // Kunci dari file config/app.php
        $privateKey = config('app.private_api_key');
        
        // Ambil header X-Api-Key
        $headerKey = $request->header('X-Api-Key'); 
        
        // Logging untuk debugging (Hapus di Production)
        Log::info('--- API KEY CHECK ---');
        Log::info('Configured (Laravel .env -> config): ' . $privateKey);
        Log::info('Received (HTTP Header): ' . $headerKey);
        Log::info('--- END CHECK ---');

        // Pengecekan aman terhadap string kosong atau mismatch
        if (empty($headerKey) || $headerKey !== $privateKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access: invalid API key'
            ], 401);
        }
        
        return null; // Lanjut jika valid
    }
    public function showAllCategory(Request $request) // Mengganti nama fungsi agar lebih spesifik
    {
        try {
            // 1. Cek private key (Diwajibkan)
            // Asumsi $this->validateAndLogApiKey() tersedia (diwariskan atau didefinisikan di sini)
            if (method_exists($this, 'validateAndLogApiKey')) {
                if ($response = $this->validateAndLogApiKey($request)) { 
                    return $response;
                }
            }

            // 2. Ambil semua data category dari database
            // Berdasarkan struktur tabel: id, name, gambar, created_at, updated_at
            $categories = Category::select('id', 'name', 'gambar', 'created_at', 'updated_at')->get();

            // 3. Kembalikan respon sukses (HTTP 200 OK)
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetched all categories',
                'count' => $categories->count(),
                'data' => $categories
            ], 200);

        } catch (Exception $e) {
            // Log error untuk debugging internal
            Log::error('Internal Server Error in ' . __METHOD__ . ': ' . $e->getMessage());
            
            // Kembalikan respon error (HTTP 500 Internal Server Error)
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menyimpan data Category baru ke database. (CREATE)
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // 1. Cek private key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Validasi input: 'name' wajib dan unik, 'gambar' wajib (asumsi URL)
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:categories,name',
                'gambar' => 'required|string|url|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400); // 400 Bad Request
            }

            // 3. Simpan data baru
            $category = Category::create([
                'name' => $request->name,
                'gambar' => $request->gambar,
            ]);

            // 4. Kembalikan respon sukses (HTTP 201 Created)
            return response()->json([
                'status' => 'success',
                'message' => 'Category created successfully',
                'data' => $category->only('id', 'name', 'gambar')
            ], 201);

        } catch (QueryException $e) {
            // Tangani error database
            Log::error('Database Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Could not save category due to database error',
                'details' => $e->getMessage()
            ], 500);
        } catch (Exception $e) {
            // Tangani error umum
            Log::error('Internal Server Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Memperbarui data Category yang ada di database. (UPDATE)
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id ID dari category yang akan diupdate
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // 1. Cek private key
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Cari Category berdasarkan ID
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category not found'
                ], 404); // 404 Not Found
            }

            // 3. Validasi input: menggunakan 'sometimes' untuk update parsial
            $validator = Validator::make($request->all(), [
                // unique:categories,name,ID_diabaikan
                'name' => 'sometimes|required|string|max:255|unique:categories,name,' . $id,
                'gambar' => 'sometimes|required|string|url|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400); // 400 Bad Request
            }

            // 4. Perbarui data
            // Fill hanya akan mengisi data yang ada di request dan lolos validasi
            $category->fill($request->all());
            $category->save();

            // 5. Kembalikan respon sukses (HTTP 200 OK)
            return response()->json([
                'status' => 'success',
                'message' => 'Category updated successfully',
                'data' => $category->only('id', 'name', 'gambar')
            ], 200);

        } catch (QueryException $e) {
            // Tangani error database
            Log::error('Database Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Could not update category due to database error',
                'details' => $e->getMessage()
            ], 500);
        } catch (Exception $e) {
            // Tangani error umum
            Log::error('Internal Server Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'details' => $e->getMessage()
            ], 500);
        }
    }

}

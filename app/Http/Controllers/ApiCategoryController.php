<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\News;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ApiCategoryController extends Controller
{
    //
    private function getTableName()
    {
        return (new Category)->getTable();
    }

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
            $categories = Category::select('id', 'name', 'gambar', 'created_at', 'updated_at')->orderBy('created_at', 'desc')->get();

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

    public function newsInCategory(Request $request, $id) // Menggunakan $id sebagai parameter rute
    {
        try {
            // 1. Cek private key (Diwajibkan)
            // Asumsi $this->validateAndLogApiKey() tersedia
            if (method_exists($this, 'validateAndLogApiKey')) {
                if ($response = $this->validateAndLogApiKey($request)) { 
                    return $response;
                }
            }

            // 2. Cek apakah kategori dengan ID tersebut ada
            $category = Category::find($id);

            if (!$category) {
                // Kembalikan respon Kategori tidak ditemukan (HTTP 404 Not Found)
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category not found with ID: ' . $id,
                    'count' => 0,
                    'data' => []
                ], 404);
            }
            
            // 3. Ambil semua data news yang memiliki categoryId yang sesuai
            // Menggunakan relasi BelongsTo di Model News: foreign key-nya adalah 'categoryId'
            // Di sini kita bisa menggunakan Query Builder atau relasi Eloquent
            $newsList = News::where('categoryId', $id)
                            ->select('id', 'title', 'contents')
                            // Opsional: Muat relasi author jika ingin menampilkan nama penulis, 
                            // asumsikan Model User ada dan relasi 'author' di News sudah didefinisikan.
                            // ->with('author:id,name') 
                            ->paginate(5);

            // 4. Kembalikan respon sukses (HTTP 200 OK)
            return response()->json([
                'status' => 'success',
                'message' => "Successfully fetched news for category: {$category->name} (ID: {$id})",
                'category_name' => $category->name,
                'count' => $newsList->count(),
                'data' => $newsList
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
        // Mulai transaksi database
        DB::beginTransaction();

        try {
            if (method_exists($this, 'validateAndLogApiKey')) {
                if ($response = $this->validateAndLogApiKey($request)) return $response;
            }

            $tableName = $this->getTableName(); 

            $validator = Validator::make($request->all(), [
                'name'   => "required|string|max:255|unique:$tableName,name",
                'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            // 1. Create data dulu tanpa gambar untuk mendapatkan ID
            $category = Category::create([
                'name'   => $request->name,
                'gambar' => null, // Biarkan null dulu
            ]);

            // 2. Proses upload gambar jika ada
            if ($request->hasFile('gambar')) {
                $image = $request->file('gambar');
                
                // Buat nama file
                $imageName = time() . '-' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                
                // Tentukan folder berdasarkan ID category yang baru dibuat
                // Hasil: media/category/1
                $relativeFolderPath = 'media/category/' . $category->id;
                $absoluteFolderPath = public_path($relativeFolderPath);

                // Cek apakah folder ID tersebut ada, jika tidak, buat foldernya
                if (!File::exists($absoluteFolderPath)) {
                    File::makeDirectory($absoluteFolderPath, 0755, true);
                }

                // Pindahkan gambar ke folder: public/media/category/{id}/namafile.jpg
                $image->move($absoluteFolderPath, $imageName);

                // 3. Update field 'gambar' di database dengan path yang benar
                $category->gambar = $relativeFolderPath . '/' . $imageName;
                $category->save();
            }

            // Commit transaksi (simpan permanen jika semua sukses)
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Category created successfully',
                'data' => $category
            ], 201);

        } catch (Exception $e) {
            // Rollback jika terjadi error (data tidak jadi masuk DB)
            DB::rollBack();
            
            Log::error($e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Memperbarui data Category yang ada di database. (UPDATE)
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id ID dari category yang akan diupdate
     * @return \Illuminate\Http\JsonResponse
     */



// --- UPDATE ---
    public function update(Request $request, $id)
    {
        try {
            if ($response = $this->validateAndLogApiKey($request)) return $response;

            $category = Category::find($id);
            if (!$category) {
                return response()->json(['status' => 'error', 'message' => 'Category not found'], 404);
            }

            // FIX: Validasi Unique harus mengecualikan ID saat ini
            // Syntax: unique:nama_tabel,nama_kolom,id_pengecualian
            $tableName = $this->getTableName();
            
            $validator = Validator::make($request->all(), [
                'name' => "sometimes|required|string|max:255|unique:$tableName,name,$id",
                'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            // Update Name
            if ($request->has('name')) {
                $category->name = $request->name;
            }

            // Update Image
            if ($request->hasFile('gambar')) {
                // Hapus gambar lama
                if ($category->gambar) {
                    $oldFilePath = public_path($category->gambar); // Path di DB sudah 'media/category/...'
                    if (File::exists($oldFilePath)) {
                        File::delete($oldFilePath);
                    }
                }

                // Upload baru
                $image = $request->file('gambar');
                $imageName = time() . '-' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('media/category/'.$id), $imageName);
                
                // Simpan path relatif
                $category->gambar = 'media/category/'.$id . '/' . $imageName;
            }

            $category->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Category updated successfully',
                'data' => $category
            ], 200);

        } catch (QueryException $e) {
            Log::error('DB Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
    

public function destroy(Request $request, $id)
    {
        try {
            // 1. Cek private key (Validasi API Key)
            if (method_exists($this, 'validateAndLogApiKey')) {
                if ($response = $this->validateAndLogApiKey($request)) {
                    return $response;
                }
            }

            // 2. Cari data Category berdasarkan ID
            $category = Category::find($id);

            // Jika tidak ditemukan
            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category not found with ID: ' . $id
                ], 404);
            }

            // --- PROSES HAPUS GAMBAR & DATA NEWS TERKAIT ---
            
            // Ambil semua news yang terkait
            $relatedNews = News::where('categoryId', $id)->get();
            $deletedNewsCount = 0;

            foreach ($relatedNews as $news) {
                // Tentukan path lengkap file gambar di folder public
                // Asumsi: file ada di public/news_images/namafile.jpg
                $newsImagePath = public_path('news_images/' . $news->image);

                // Cek apakah file ada, lalu hapus
                if ($news->image && File::exists($newsImagePath)) {
                    File::delete($newsImagePath);
                }

                // Hapus record news dari database
                $news->delete();
                $deletedNewsCount++;
            }

            // --- PROSES HAPUS GAMBAR & DATA CATEGORY ---

            // Tentukan path lengkap gambar category
            // Asumsi: file ada di public/categories/namafile.jpg
            $categoryImagePath = public_path('categories/' . $category->image);

            // Cek apakah file ada, lalu hapus
            if ($category->image && File::exists($categoryImagePath)) {
                File::delete($categoryImagePath);
            }

            // Simpan nama category untuk response sebelum dihapus
            $categoryName = $category->name;

            // Hapus Category itu sendiri
            $category->delete();

            // 5. Kembalikan respon sukses
            return response()->json([
                'status' => 'success',
                'message' => 'Category and associated news deleted successfully',
                'data' => [
                    'deleted_category_id' => $id,
                    'deleted_category_name' => $categoryName,
                    'associated_news_deleted' => $deletedNewsCount
                ]
            ], 200);

        } catch (QueryException $e) {
            Log::error('Database Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Could not delete category due to database error',
                'details' => $e->getMessage()
            ], 500);
        } catch (Exception $e) {
            Log::error('Internal Server Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'details' => $e->getMessage()
            ], 500);
        }
    }


}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ApiAuthenticateControllers extends Controller
{
    /**
     * Helper untuk validasi API Key dan Logging
     */
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

    /**
     * Handle a user registration request.
     */
    public function register(Request $request)
    {
        try {
            // Hapus blok API Key ini jika Anda tidak memerlukannya untuk endpoint register.
            // if ($response = $this->validateAndLogApiKey($request)) {
            //     return $response;
            // }

            // 1. Validasi input
            $validator = Validator::make($request->all(), [
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users',
                'password' => 'required|string|min:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // 2. Buat user baru 
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => 'USER',
            ]);

            // 3. Kembalikan respon sukses (tanpa password hash)
            return response()->json([
                'status'  => 'success',
                'message' => 'User created successfully',
                'user'    => [ // Menggunakan kunci 'user' sesuai respons ideal
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ], 201);

        } catch (QueryException $e) { 
            Log::error('Database Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Database error: Could not create user.'
            ], 500);

        } catch (Exception $e) {
            Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Internal server error.'
            ], 500);
        }
    }

    /**
     * Handle a user login request.
     */
    public function login(Request $request)
    {
        try {
            // Hapus blok API Key ini jika Anda tidak memerlukannya untuk endpoint login.
            // if ($response = $this->validateAndLogApiKey($request)) {
            //     return $response;
            // }

            // 1. Validasi input
            // Klien mengirim 'admin@address.com' di kolom 'name', ini yang divalidasi
            $validator = Validator::make($request->all(), [
                'name' => 'required|string', 
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // 2. PERBAIKAN UTAMA: Cari user berdasarkan EMAIL
            // Karena klien mengirim alamat email di field 'name', kita cari di kolom 'email'.
            $user = User::where('email', $request->name)->first();
            
            // 3. Verifikasi user dan password
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'name atau password salah.'
                ], 401);
            }
            
            // 4. Jika berhasil, kirim data user
            return response()->json([
                'status'  => 'success',
                'message' => 'Login successful',
                'data'    => [ // Menggunakan kunci 'data' sesuai respons Login ideal
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('General Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Internal server error.'
            ], 500);
        }
    }
    
    /**
     * Method untuk mengambil semua user (Memerlukan API Key)
     */
    public function showAll(Request $request)
    {
        try {
            // 1. Cek private key (Diwajibkan)
            if ($response = $this->validateAndLogApiKey($request)) { 
                return $response;
            }

            // 2. Ambil semua data pengguna dari database (hanya kolom yang aman)
            $users = User::select('id', 'name', 'email', 'role', 'created_at', 'updated_at')->get();

            // 3. Kembalikan respon sukses
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully fetched all users',
                'count' => $users->count(),
                'data' => $users
            ], 200);

        } catch (Exception $e) {
            Log::error('Internal Server Error in ' . __METHOD__ . ': ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function updateRoleToEditor(Request $request, $id)
    {
        try {
            // 1. Cek private key (Diwajibkan)
            // Asumsikan method ini ada dalam class Controller yang sama
            if ($response = $this->validateAndLogApiKey($request)) {
                return $response;
            }

            // 2. Cari pengguna berdasarkan ID
            $user = User::find($id);

            // 3. Cek apakah pengguna ditemukan
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }

            // 4. Perbarui role pengguna
            $user->role = 'EDITOR';
            $user->save();

            // 5. Kembalikan respon sukses
            return response()->json([
                'status' => 'success',
                'message' => "Successfully updated user ID {$id} role to EDITOR",
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'updated_at' => $user->updated_at
                ]
            ], 200);

        } catch (Exception $e) {
            // Log error
            Log::error('Internal Server Error in ' . __METHOD__ . ': ' . $e->getMessage());

            // Kembalikan respon error
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    
    /**
     * Handle update user data (Name, Email, Password, Role).
     */
public function update(Request $request, $id)
{
    // 1. Cek API Key sebaiknya via Middleware, tapi jika harus di sini:
    if ($response = $this->validateAndLogApiKey($request)) {
        return $response;
    }

    $user = User::find($id);

    if (!$user) {
        return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
    }

    // 2. Validasi (Gunakan 'nullable' sebagai ganti 'sometimes' untuk kejelasan modern)
    $validated = $request->validate([
        'name'     => 'nullable|string|max:255',
        'email'    => 'nullable|email|unique:users,email,' . $user->id,
        'password' => 'nullable|string|min:6',
        'role'     => 'nullable|string|in:USER,ADMIN,EDITOR', // Pastikan ada whitelist role
    ]);

    DB::beginTransaction(); // 3. Gunakan Transaction untuk data yang saling berelasi
    try {
        // 4. Handle Password Hashing (hanya jika ada)
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // 5. Handle Side Effect Role Change
        // Perbaikan Logic: Apakah maksudnya jika diganti JADI User biasa, berita jadi draft?
        // Asumsi kode lama: Jika role berubah dan bukan USER (misal jadi admin?), draft berita.
        // Silakan sesuaikan logic if-nya sesuai kebutuhan bisnis.
        if ($request->has('role') && $request->role !== $user->role) {
             // Contoh logika: Jika didemosi ke USER, arsipkan berita
             if ($request->role === 'USER') { 
                $newsUpdatedCount = News::where('authorId', $user->id)->update(['status' => 'DRAFT']);
                Log::info("User {$user->id} demoted to USER. {$newsUpdatedCount} news set to DRAFT.");
             }
        }

        // 6. Update user secara otomatis dengan data yang sudah divalidasi
        // fill() hanya akan mengisi field yang ada di array $validated
        $user->fill($validated);
        
        if ($user->isDirty()) { // Cek apakah ada perubahan sebelum save
            $user->save();
        }

        DB::commit();

        return response()->json([
            'status'  => 'success',
            'message' => 'User updated successfully',
            'data'    => $user->refresh() // Refresh untuk mendapatkan data terbaru (termasuk updated_at)
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Update User Error (ID: $id): " . $e->getMessage());
        
        return response()->json([
            'status'  => 'error',
            'message' => 'Internal server error.'
        ], 500);
    }
}



    
}
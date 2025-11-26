<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

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
}
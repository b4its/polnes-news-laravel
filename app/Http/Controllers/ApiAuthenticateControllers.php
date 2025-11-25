<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApiAuthenticateControllers extends Controller
{
    //
    public function register_views(Request $request)
    {
        try {
            // 1. Cek private key
            $privateKey = config('app.private_api_key');
            $headerKey = $request->header('x-api-key');

            if (!$headerKey || $headerKey !== $privateKey) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access: invalid API key'
                ], 401);
            }

            // 2. Validasi input
            $validator = Validator::make($request->all(), [
                'username' => 'required|string|max:255',
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

            // 3. Buat user baru
            $user = User::create([
                'username' => $request->username,
                'email'    => $request->email,
                'password' => Hash::make($request->password)
            ]);

            // 4. Kembalikan respon sukses
            return response()->json([
                'status'  => 'success',
                'message' => 'User created successfully',
                'user'    => $user
            ], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            // Kesalahan query database (misalnya duplikat email, kolom null, dsb)
            return response()->json([
                'status'  => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);

        } catch (Exception $e) {
            // Kesalahan umum lainnya (server, logic, dsb)
            return response()->json([
                'status'  => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle a user login request.
     */
    public function login_views(Request $request)
    {
        try {
            // 1. Cek private key, sama seperti di register
            $privateKey = config('app.private_api_key');
            $headerKey = $request->header('x-api-key');

            if (!$headerKey || $headerKey !== $privateKey) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access: invalid API key'
                ], 401); // 401 Unauthorized
            }

            // 2. Validasi input
            $validator = Validator::make($request->all(), [
                'username' => 'required|string',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422); // 422 Unprocessable Entity
            }

            // 3. Cari user berdasarkan username
            $user = User::where('username', $request->username)->first();
            // 4. Verifikasi user dan password
            // Jika user tidak ditemukan ATAU password tidak cocok
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Username atau password salah.'
                ], 401); // 401 Unauthorized
            }
            
            // Jika berhasil, kirim data user
            // Respon ini cocok dengan yang diharapkan oleh Flutter
            return response()->json([
                'status'  => 'success',
                'message' => 'Login successful',
                'data'    => $user // Menggunakan 'data' agar konsisten dengan ekspektasi Flutter
            ], 200); // 200 OK

        } catch (Exception $e) {
            // Menangkap kesalahan tak terduga
            return response()->json([
                'status'  => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }
}

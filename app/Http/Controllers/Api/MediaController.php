<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File; // Menggunakan File facade

class MediaController extends Controller
{
    /**
     * Verifikasi API Key dari Header Authorization.
     */
    private function isAuthorized(Request $request): bool
    {
        $clientKey = $request->bearerToken();
        $validKey = env('LICENSE_API_KEY');

        if (!$clientKey || !$validKey) {
            return false;
        }
        return hash_equals($validKey, $clientKey);
    }

    /**
     * Mengambil dan menampilkan file dari folder 'public', bukan 'storage'.
     *
     * @param Request $request
     * @param string $path Path file yang diminta (bisa termasuk subdirektori di dalam public)
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $path)
    {
        // 1. Cek Otorisasi
        if (!$this->isAuthorized($request)) {
            return response()->json(
                ['message' => 'Unauthorized: Invalid API Key'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // 2. Keamanan: Mencegah Directory Traversal Attack (contoh: ../../.env)
        // Normalisasi path dan pastikan tidak mengandung '..'
        $normalizedPath = str_replace('\\', '/', $path);
        if (Str::contains($normalizedPath, '..')) {
             return response()->json(
                ['message' => 'Invalid path'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // 3. Gabungkan dengan path media utama (sesuai route)
        // URL: /api/media/gambar.jpg -> Path: public/media/gambar.jpg
        $fullPath = "{$normalizedPath}";

        // 4. Bangun path absolut ke file di dalam folder public
        $filePath = public_path($fullPath);

        // 5. Cek apakah file ada di folder public dan merupakan file (bukan direktori)
        if (!File::exists($filePath) || !File::isFile($filePath)) {
            return response()->json(
                ['message' => 'File not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        // 6. Jika file ada, kirimkan sebagai respons.
        $fileContent = File::get($filePath);
        $mimeType = File::mimeType($filePath);

        // Buat respons dengan header yang benar.
        return response($fileContent, 200)->header('Content-Type', $mimeType);
    }
}
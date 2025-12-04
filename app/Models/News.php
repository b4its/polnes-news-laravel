<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class News extends Model
{
    use HasFactory;

    protected $table = 'news';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     * Pastikan semua kolom di migration ada di sini.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'categoryId',
        'gambar',
        'contents',
        'authorId',
        'views',
        'thumbnail',
        'linkYoutube',
        'status',
    ];

    /**
     * Relasi: Berita milik satu Kategori.
     */
    public function category(): BelongsTo
    {
        // Asumsi Model Category sudah ada
        return $this->belongsTo(Category::class, 'categoryId');
    }

    /**
     * Relasi: Berita ditulis oleh satu User (Author).
     */
    public function author(): BelongsTo
    {
        // Asumsi Model User sudah ada
        return $this->belongsTo(User::class, 'authorId');
    }
}
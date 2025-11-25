<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class News extends Model
{
    //
    use HasFactory;

    protected $table = 'news';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'categoryId',
        'gambar',
        'content',
        'authorId',
        'views',
        'linkYoutube',
        'status',
    ];

    public function categoryList(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoryId');
    }

    public function user(): BelongsTo
    {
        // Kita perlu mendefinisikan foreign key secara eksplisit
        // karena nama kolom 'idUsers' tidak mengikuti konvensi Laravel ('user_id').
        return $this->belongsTo(User::class, 'authorId');
    }
}

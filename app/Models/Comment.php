<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    //

    use HasFactory;

    protected $table = 'comment';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'userId',
        'newsId',
        'rating',
        'status',
    ];

    public function newsList(): BelongsTo
    {
        return $this->belongsTo(News::class, 'newsId');
    }

    public function user(): BelongsTo
    {
        // Kita perlu mendefinisikan foreign key secara eksplisit
        // karena nama kolom 'idUsers' tidak mengikuti konvensi Laravel ('user_id').
        return $this->belongsTo(User::class, 'userId');
    }
}

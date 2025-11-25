<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    //
        
        // Schema::create('notification', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('title')->nullable();
        //     $table->foreignId('newsId')->nullable()->constrained('news')->onDelete('cascade');
        //     $table->text('gambar')->nullable();
        //     $table->timestamps();
        // });
    use HasFactory;

    protected $table = 'notification';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'newsId',
        'gambar',
    ];

    public function newsList(): BelongsTo
    {
        return $this->belongsTo(News::class, 'newsId');
    }
    
}

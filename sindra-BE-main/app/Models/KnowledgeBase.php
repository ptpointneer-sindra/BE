<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


/**
 * @OA\Schema(
 *     schema="KnowledgeBase",
 *     type="object",
 *     title="Knowledge Base",
 *     description="Schema untuk entitas Knowledge Base",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="author_id", type="integer", example=1),
 *     @OA\Property(property="category_id", type="integer", example=2),
 *     @OA\Property(property="title", type="string", example="Panduan Menggunakan Sistem"),
 *     @OA\Property(property="slug", type="string", example="panduan-menggunakan-sistem"),
 *     @OA\Property(property="content", type="string", example="Berikut panduan lengkap untuk menggunakan sistem."),
 *     @OA\Property(property="is_published", type="boolean", example=true),
 *     @OA\Property(property="published_at", type="string", format="date-time", example="2025-10-15T10:00:00Z"),
 *     @OA\Property(
 *         property="category",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="id", type="integer", example=2),
 *         @OA\Property(property="name", type="string", example="Panduan Sistem")
 *     ),
 *     @OA\Property(
 *         property="author",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Admin Utama")
 *     )
 * )
 */

class KnowledgeBase extends Model
{
    use HasFactory;

    protected $fillable = [
        'author_id',
        'category_id',
        'title',
        'brief',
        'slug',
        'image_url',
        'content',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    // Relasi ke kategori
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relasi ke user (author)
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // Generate slug otomatis saat create/update
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($kb) {
            $kb->slug = Str::slug($kb->title);
        });

        static::updating(function ($kb) {
            if ($kb->isDirty('title')) {
                $kb->slug = Str::slug($kb->title);
            }
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


/**
 * @OA\Tag(
 *     name="Categories",
 *     description="API untuk mengelola kategori artikel atau knowledge base"
 */

/**
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     title="Category",
 *     description="Data kategori",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Teknologi"),
 *     @OA\Property(property="slug", type="string", example="teknologi"),
 *     @OA\Property(property="description", type="string", example="Kategori artikel seputar teknologi terbaru"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-15T09:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-15T09:00:00Z")
 * )
 */

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    // Generate slug otomatis saat menyimpan
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            $category->slug = Str::slug($category->name);
        });

        static::updating(function ($category) {
            if ($category->isDirty('name')) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}

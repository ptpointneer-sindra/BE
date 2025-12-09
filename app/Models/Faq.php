<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Faq",
 *     type="object",
 *     title="FAQ",
 *     description="Schema untuk entitas FAQ",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="question", type="string", example="Bagaimana cara reset password?"),
 *     @OA\Property(property="answer", type="string", example="Klik menu lupa password di halaman login."),
 *     @OA\Property(property="is_published", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-15T08:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-15T08:30:00Z")
 * )
 */


class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'answer',
        'is_published',
    ];
}

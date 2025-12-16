<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RequestChangeAttachment extends Model
{
    use HasFactory;

    protected $table = 'request_change_attachments';

    protected $fillable = [
        'request_change_id',
        'file_path',
    ];

    /**
     * Relationship: Attachment belongs to RequestChange
     */
    public function requestChange()
    {
        return $this->belongsTo(RequestChange::class);
    }
}

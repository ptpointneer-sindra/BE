<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketDiscussionAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'discussion_id',
        'file_path',
        'file_name',
    ];

    public function discussion()
{
    return $this->belongsTo(TicketDiscussion::class, 'discussion_id');
}
}

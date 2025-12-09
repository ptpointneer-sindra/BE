<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RequestChange extends Model
{
    use HasFactory;

    protected $table = 'request_changes';

    protected $fillable = [
        'ticket_id',
        'reporter_id',
        'asset_uuid',
        'description',
        'status',
        'status_implement',
        'config_comment',
        'requested_at',
        'reviewed_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Relationship: RequestChange belongs to Ticket
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Relationship: RequestChange belongs to Reporter (User)
     */
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Relationship: RequestChange has many Attachments
     */
    public function attachments()
    {
        return $this->hasMany(RequestChangeAttachment::class);
    }
}

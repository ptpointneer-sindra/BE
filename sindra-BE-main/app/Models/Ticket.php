<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Ticket",
 *     type="object",
 *     title="Ticket",
 *     description="Data tiket pengaduan",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="asset_uuid", type="string", format="uuid", example="e7b8d2a9-1f4b-4e90-9a61-bd7e93e56a12"),
 *     @OA\Property(property="reporter_id", type="integer", example=5),
 *     @OA\Property(property="title", type="string", example="Kerusakan Laptop"),
 *     @OA\Property(property="description", type="string", example="Layar laptop pecah setelah digunakan."),
 *     @OA\Property(property="status", type="string", enum={"open", "in_progress", "resolved", "closed"}, example="open"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-15T08:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-15T10:00:00Z"),
 *     @OA\Property(
 *         property="assignees",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="user_id", type="integer", example=3),
 *             @OA\Property(property="name", type="string", example="Budi Hartono")
 *         )
 *     ),
 *     @OA\Property(
 *         property="attachments",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="file_name", type="string", example="bukti-foto.jpg"),
 *             @OA\Property(property="file_path", type="string", example="storage/tickets/bukti-foto.jpg"),
 *             @OA\Property(property="mime_type", type="string", example="image/jpeg")
 *         )
 *     )
 * )
 */


class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_uuid',
        'reporter_id',
        'ticket_category_id',
        'instansi_id',
        'priority',
        'code',
        'title',
        'description',
        'status',
    ];

    // === RELATIONSHIPS ===
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function instansi()
    {
        return $this->belongsTo(Instansi::class, 'instansi_id');
    }

    public function feedback() {
        return $this->hasOne(TicketFeedback::class, 'ticket_id');
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'ticket_assignees')
            ->withTimestamps();
    }

    public function reopen()
    {
        return $this->hasOne(TicketReopen::class, 'ticket_id');
    }

    public function category()
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    public function log()
    {
        return $this->hasMany(TicketLog::class, 'ticket_id');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function discussions()
    {
        return $this->hasMany(TicketDiscussion::class);
    }

    public function escalates()
    {
        return $this->hasMany(TicketEscalate::class, 'ticket_id');
    }

    protected static function booted()
    {
        static::creating(function ($ticket) {

            // gunakan created_at yang di-set seeder, atau now()
            $createdDate = $ticket->created_at ?? now();

            // Generate CODE
            if (empty($ticket->code)) {

                $date = $createdDate->format('Ymd');

                $lastTicket = Ticket::whereDate('created_at', $createdDate->toDateString())
                    ->orderBy('id', 'desc')
                    ->first();

                $number = $lastTicket
                    ? ((int) substr($lastTicket->code, -4)) + 1
                    : 1;

                $sequence = str_pad($number, 4, '0', STR_PAD_LEFT);

                $ticket->code = "TCK-$date-$sequence";
            }

            // Deadline default (gunakan created_at)
            if (empty($ticket->deadline_at)) {
                $ticket->deadline_at = $createdDate->copy()->addHours(8);
            }
        });
    }


}

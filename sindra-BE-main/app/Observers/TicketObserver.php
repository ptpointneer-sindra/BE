<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Models\TicketLog;
use Illuminate\Support\Facades\Auth;

class TicketObserver
{
    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket)
    {
        $userId = $ticket->reporter->id;

        TicketLog::addLog(
            $ticket->id,
            $userId, // fallback user if seeder
            "Ticket Created",
            "Ticket {$ticket->code} dibuat"
        );
    }

    public function updated(Ticket $ticket)
    {
        $changes = $ticket->getChanges();
        unset($changes['updated_at']);

        if (!empty($changes)) {
            $detail = [];

            foreach ($changes as $field => $new) {
                $old = $ticket->getOriginal($field);
                $detail[] = "$field: '$old' -> '$new'";
            }

            TicketLog::addLog(
                $ticket->id,
                Auth::id() ?? 1,
                implode(", ", $detail),
                "Ticket {$ticket->code} telah diperbarui"
            );
        }
    }


    /**
     * Handle the Ticket "deleted" event.
     */
    public function deleted(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "restored" event.
     */
    public function restored(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "force deleted" event.
     */
    public function forceDeleted(Ticket $ticket): void
    {
        //
    }
}

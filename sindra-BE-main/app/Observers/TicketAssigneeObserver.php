<?php

namespace App\Observers;

use App\Models\TicketAssignee;
use App\Models\TicketLog;
use Illuminate\Support\Facades\Auth;

class TicketAssigneeObserver
{
    /**
     * Handle the TicketAssignee "created" event.
     */
    public function created(TicketAssignee $assignee)
    {
        $ticket = $assignee->ticket;
        $user   = $assignee->user;



        TicketLog::addLog(
            $ticket->id,
            $assignee->user->id,
            "Assigned to {$user->name}",
            "Ticket {$ticket->code} di-assign ke {$user->name}"
        );
    }

    /**
     * Handle the TicketAssignee "updated" event.
     */
    public function updated(TicketAssignee $ticketAssignee): void
    {
        //
    }

    /**
     * Handle the TicketAssignee "deleted" event.
     */
    public function deleted(TicketAssignee $ticketAssignee): void
    {
        //
    }

    /**
     * Handle the TicketAssignee "restored" event.
     */
    public function restored(TicketAssignee $ticketAssignee): void
    {
        //
    }

    /**
     * Handle the TicketAssignee "force deleted" event.
     */
    public function forceDeleted(TicketAssignee $ticketAssignee): void
    {
        //
    }
}

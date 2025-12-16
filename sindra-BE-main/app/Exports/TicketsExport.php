<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class TicketsExport implements FromCollection
{
    protected $tickets;

    public function __construct($tickets)
    {
        $this->tickets = $tickets;
    }

    public function collection()
    {
        return $this->tickets->map(function ($t) {
            return [
                'Ticket Code' => $t->code,
                'Title' => $t->title,
                'Status' => $t->status,
                'Priority' => $t->priority,
                'Category' => optional($t->category)->name,
                'Reporter' => optional($t->reporter)->name,
                'Created At' => $t->created_at->format('Y-m-d H:i'),
                'Deadline' => $t->deadline,
                'Resolved At' => $t->resolved_at,
            ];
        });
    }
}

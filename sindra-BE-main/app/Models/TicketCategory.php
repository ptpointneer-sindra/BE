<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketCategory extends Model
{
    use SoftDeletes;

    protected $table = 'ticket_categories';

    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    // Jika ingin otomatis mengisi slug dari name, bisa pakai mutator atau observer
}

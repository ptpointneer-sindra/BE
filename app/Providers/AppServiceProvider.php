<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Models\TicketAssignee;
use App\Observers\TicketAssigneeObserver;
use App\Observers\TicketObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (env('APP_ENV') !== 'local') {
        URL::forceScheme('https');
        
        }
        Ticket::observe(TicketObserver::class);
        TicketAssignee::observe(TicketAssigneeObserver::class);
        
    }

}

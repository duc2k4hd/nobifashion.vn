<?php

namespace App\Providers;

use App\Events\AddressChanged;
use App\Listeners\LogAddressAudit;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AddressChanged::class => [
            LogAddressAudit::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}


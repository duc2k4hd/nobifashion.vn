<?php

namespace App\Listeners;

use App\Events\AddressChanged;
use App\Models\AddressAudit;

class LogAddressAudit
{
    public function handle(AddressChanged $event): void
    {
        AddressAudit::create([
            'address_id' => $event->address->id,
            'performed_by' => $event->performedBy,
            'action' => $event->action,
            'description' => $event->description,
            'changes' => empty($event->changes) ? null : $event->changes,
        ]);
    }
}


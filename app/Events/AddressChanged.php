<?php

namespace App\Events;

use App\Models\Address;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AddressChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Address $address,
        public string $action,
        public ?int $performedBy = null,
        public ?string $description = null,
        public array $changes = []
    ) {
    }
}


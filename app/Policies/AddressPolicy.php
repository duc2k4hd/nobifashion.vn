<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Address;

class AddressPolicy
{
    public function view(Account $user, Address $address): bool
    {
        return $user->isAdmin() || $address->account_id === $user->id;
    }

    public function update(Account $user, Address $address): bool
    {
        return $user->isAdmin() || $address->account_id === $user->id;
    }

    public function delete(Account $user, Address $address): bool
    {
        return $user->isAdmin() || $address->account_id === $user->id;
    }
}


<?php

namespace App\Policies;

use App\Models\Account;

class AccountPolicy
{
    public function before(Account $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(Account $user): bool
    {
        return $user->isStaff();
    }

    public function view(Account $user, Account $target): bool
    {
        return $this->viewAny($user);
    }

    public function create(Account $user): bool
    {
        return $user->isStaff();
    }

    public function update(Account $user, Account $target): bool
    {
        if ($target->isAdmin() && !$user->isAdmin()) {
            return false;
        }

        return $user->isStaff();
    }

    public function delete(Account $user, Account $target): bool
    {
        if ($target->id === $user->id || $target->isAdmin()) {
            return false;
        }

        return $user->isAdmin();
    }

    public function changeRole(Account $user, Account $target): bool
    {
        if ($target->id === $user->id) {
            return false;
        }

        return $user->isAdmin();
    }

    public function resetPassword(Account $user, Account $target): bool
    {
        if ($target->isAdmin() && !$user->isAdmin()) {
            return false;
        }

        return $user->isStaff();
    }

    public function forceLogout(Account $user, Account $target): bool
    {
        if ($target->id === $user->id) {
            return true;
        }

        return $this->resetPassword($user, $target);
    }
}

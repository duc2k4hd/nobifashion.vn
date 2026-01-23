<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Tag;

class TagPolicy
{
    /**
     * Determine if the user can view any tags.
     */
    public function viewAny(Account $account): bool
    {
        // Admin và Editor có thể xem
        return in_array($account->role, ['admin', 'editor']);
    }

    /**
     * Determine if the user can view the tag.
     */
    public function view(Account $account, Tag $tag): bool
    {
        return in_array($account->role, ['admin', 'editor']);
    }

    /**
     * Determine if the user can create tags.
     */
    public function create(Account $account): bool
    {
        // Admin, Editor, Writer có thể tạo
        return in_array($account->role, ['admin', 'editor', 'writer']);
    }

    /**
     * Determine if the user can update the tag.
     */
    public function update(Account $account, Tag $tag): bool
    {
        // Admin và Editor có thể sửa
        return in_array($account->role, ['admin', 'editor']);
    }

    /**
     * Determine if the user can delete the tag.
     */
    public function delete(Account $account, Tag $tag): bool
    {
        // Chỉ Admin có thể xóa
        return $account->role === 'admin';
    }

    /**
     * Determine if the user can restore the tag.
     */
    public function restore(Account $account, Tag $tag): bool
    {
        return $account->role === 'admin';
    }

    /**
     * Determine if the user can permanently delete the tag.
     */
    public function forceDelete(Account $account, Tag $tag): bool
    {
        return $account->role === 'admin';
    }
}


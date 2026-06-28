<?php

namespace App\Policies;

use App\Models\LanguageCenter;
use App\Models\User;

class LanguageCenterPolicy
{
    /** Super-admin can do anything on any center. */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    /** Only super-admin creates/lists all centers (handled by before). */
    public function create(User $user): bool
    {
        return false;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    /** A center_admin may view/manage their own center. */
    public function view(User $user, LanguageCenter $center): bool
    {
        return $this->ownsCenter($user, $center) && $user->centerRoleIs('center_admin');
    }

    public function update(User $user, LanguageCenter $center): bool
    {
        return $this->ownsCenter($user, $center) && $user->centerRoleIs('center_admin');
    }

    public function delete(User $user, LanguageCenter $center): bool
    {
        return false; // super-admin only (before)
    }

    protected function ownsCenter(User $user, LanguageCenter $center): bool
    {
        return $user->center()?->id === $center->id;
    }
}

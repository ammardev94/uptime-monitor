<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

/**
 * Admins can do everything (see before()). Other users can only touch sites linked
 * to their own department, and only the actions their role grants.
 */
class SitePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Site $site): bool
    {
        return $user->canAccessSite($site);
    }

    public function check(User $user, Site $site): bool
    {
        return $user->hasPermission('sites.check') && $user->canAccessSite($site);
    }

    public function pause(User $user, Site $site): bool
    {
        return $user->hasPermission('sites.pause') && $user->canAccessSite($site);
    }

    public function update(User $user, Site $site): bool
    {
        return $user->hasPermission('sites.edit') && $user->canAccessSite($site);
    }

    public function managePages(User $user, Site $site): bool
    {
        return $user->hasPermission('pages.manage') && $user->canAccessSite($site);
    }

    // Creating, deleting and assigning sites to departments is admin-only.
    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Site $site): bool
    {
        return false;
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ZohoSyncLog;

class ZohoSyncLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('zoho.view') || $user->isSuperAdmin() || $user->isAccountant();
    }

    public function view(User $user, ZohoSyncLog $log): bool
    {
        return $user->can('zoho.view') || $user->isSuperAdmin() || $user->isAccountant();
    }

    public function sync(User $user): bool
    {
        return $user->can('zoho.sync') || $user->isSuperAdmin() || $user->isAccountant();
    }
}

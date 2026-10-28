<?php

namespace App\Policies;

use App\Models\Transfer;
use App\Models\User;

class TransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('transfers.view');
    }

    public function view(User $user, Transfer $transfer): bool
    {
        if (!$user->can('transfers.view')) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isAccountant() || $user->all_branches) {
            return true;
        }

        return $transfer->to_user_id === $user->id || $transfer->from_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('transfers.create');
    }

    public function update(User $user, Transfer $transfer): bool
    {
        return $user->can('transfers.edit') && $transfer->status === 'pending';
    }

    public function approve(User $user, Transfer $transfer): bool
    {
        return $user->can('transfers.approve') && $transfer->status === 'pending';
    }
}

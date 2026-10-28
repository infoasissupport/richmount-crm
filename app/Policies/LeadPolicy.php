<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leads.view');
    }

    public function view(User $user, Lead $lead): bool
    {
        if ($user->can('leads.view')) {
            if ($user->isSuperAdmin() || $user->isAccountant()) {
                return true;
            }
            if ($user->isEstimation()) {
                return $user->all_branches || $user->branch_id === $lead->branch_id;
            }
            if ($user->isEngineer()) {
                return $lead->assigned_engineer_id === $user->id
                    && $lead->status !== 'Closed'
                    && !in_array($lead->stage, ['sent', 'awarded']);
            }
        }
        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('leads.create');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->can('leads.edit');
    }

    public function assign(User $user, Lead $lead): bool
    {
        return $user->can('leads.assign');
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->can('leads.delete') && $lead->quotations()->count() === 0;
    }
}

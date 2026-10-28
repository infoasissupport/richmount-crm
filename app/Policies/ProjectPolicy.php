<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('projects.view');
    }

    public function view(User $user, Project $project): bool
    {
        if (!$user->can('projects.view')) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isAccountant() || $user->isEstimation() || $user->all_branches) {
            return true;
        }

        if ($user->isEngineer()) {
            return $project->engineer_id === $user->id || $project->branch_id === $user->branch_id;
        }

        return $project->branch_id === $user->branch_id;
    }

    public function create(User $user): bool
    {
        return $user->can('projects.create');
    }

    public function open(User $user, Project $project): bool
    {
        return $user->can('projects.open') && $project->status === 'popending';
    }

    public function update(User $user, Project $project): bool
    {
        if (!$user->can('projects.edit')) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isAccountant()) {
            return true;
        }

        if ($user->isEngineer()) {
            return $project->engineer_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->isSuperAdmin() && in_array($project->status, ['popending', 'open']);
    }
}

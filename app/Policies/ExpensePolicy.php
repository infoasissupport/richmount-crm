<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('expenses.view');
    }

    public function view(User $user, Expense $expense): bool
    {
        if (!$user->can('expenses.view')) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isAccountant() || $user->all_branches) {
            return true;
        }

        return $expense->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('expenses.create');
    }

    public function approve(User $user, Expense $expense): bool
    {
        return $user->can('expenses.approve') && $expense->status === 'pending';
    }

    public function delete(User $user, Expense $expense): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $expense->user_id === $user->id && $expense->status === 'pending';
    }
}

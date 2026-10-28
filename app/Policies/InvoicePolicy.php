<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if (!$user->can('invoices.view')) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isAccountant() || $user->all_branches) {
            return true;
        }

        return $invoice->branch_id === $user->branch_id;
    }

    public function create(User $user): bool
    {
        return $user->can('invoices.create');
    }

    public function pay(User $user, Invoice $invoice): bool
    {
        return $user->can('payments.create') && $invoice->balance_due > 0;
    }
}

<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('quotes.view');
    }

    public function view(User $user, Quotation $quote): bool
    {
        if ($user->can('quotes.view')) {
            if ($user->isSuperAdmin() || $user->isAccountant()) {
                return true;
            }
            if ($user->isEstimation()) {
                return $user->canAccessBranch($quote->branch_id);
            }
            if ($user->isEngineer()) {
                return $quote->prepared_by_id === $user->id
                    || ($quote->lead && $quote->lead->assigned_engineer_id === $user->id);
            }
        }
        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('quotes.create');
    }

    public function update(User $user, Quotation $quote): bool
    {
        if (!$user->can('quotes.edit')) {
            return false;
        }

        // Super Admin can edit anytime before awarded
        if ($user->isSuperAdmin()) {
            return $quote->status !== 'awarded';
        }

        // Estimation User can edit within their branch anytime before awarded
        if ($user->isEstimation()) {
            return $user->canAccessBranch($quote->branch_id) && $quote->status !== 'awarded';
        }

        // Engineer can only edit draft or rejected quotes prepared by them
        return in_array($quote->status, ['draft', 'rejected']) && $quote->prepared_by_id === $user->id;
    }

    public function submit(User $user, Quotation $quote): bool
    {
        if (!$user->can('quotes.submit') || !in_array($quote->status, ['draft', 'rejected'])) {
            return false;
        }

        if ($user->isEstimation()) {
            return $user->canAccessBranch($quote->branch_id);
        }

        if ($user->isEngineer()) {
            return $quote->prepared_by_id === $user->id;
        }

        return true;
    }

    public function approve(User $user, Quotation $quote): bool
    {
        if (!$user->can('quotes.approve') || $quote->status !== 'pending') {
            return false;
        }

        if ($user->isEstimation()) {
            return $user->canAccessBranch($quote->branch_id);
        }

        return true;
    }

    public function reject(User $user, Quotation $quote): bool
    {
        if (!$user->can('quotes.approve') || $quote->status !== 'pending') {
            return false;
        }

        if ($user->isEstimation()) {
            return $user->canAccessBranch($quote->branch_id);
        }

        return true;
    }

    public function send(User $user, Quotation $quote): bool
    {
        if (!$user->can('quotes.send') || !in_array($quote->status, ['approved', 'draft'])) {
            return false;
        }

        if ($user->isEstimation()) {
            return $user->canAccessBranch($quote->branch_id);
        }

        return true;
    }

    public function award(User $user, Quotation $quote): bool
    {
        if (!$user->can('quotes.award') || !in_array($quote->status, ['sent', 'approved'])) {
            return false;
        }

        if ($user->isEstimation()) {
            return $user->canAccessBranch($quote->branch_id);
        }

        return true;
    }

    public function reopen(User $user, Quotation $quote): bool
    {
        if (!$user->can('quotes.edit') || !in_array($quote->status, ['approved', 'sent'])) {
            return false;
        }

        if ($user->isEstimation()) {
            return $user->canAccessBranch($quote->branch_id);
        }

        return true;
    }
}

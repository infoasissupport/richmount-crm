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
            if ($user->isSuperAdmin() || $user->isEstimation() || $user->isAccountant()) {
                return true;
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

        // Estimator or Super Admin can edit anytime before awarded
        if ($user->isSuperAdmin() || $user->isEstimation()) {
            return $quote->status !== 'awarded';
        }

        // Engineer can only edit draft or rejected quotes
        return in_array($quote->status, ['draft', 'rejected']) && $quote->prepared_by_id === $user->id;
    }

    public function submit(User $user, Quotation $quote): bool
    {
        return $user->can('quotes.submit') && in_array($quote->status, ['draft', 'rejected']);
    }

    public function approve(User $user, Quotation $quote): bool
    {
        return $user->can('quotes.approve') && $quote->status === 'pending';
    }

    public function reject(User $user, Quotation $quote): bool
    {
        return $user->can('quotes.approve') && $quote->status === 'pending';
    }

    public function send(User $user, Quotation $quote): bool
    {
        return $user->can('quotes.send') && in_array($quote->status, ['approved', 'draft']);
    }

    public function award(User $user, Quotation $quote): bool
    {
        return $user->can('quotes.award') && in_array($quote->status, ['sent', 'approved']);
    }
}

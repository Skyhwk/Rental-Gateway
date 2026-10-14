<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user)
    {
        return $user->can('invoices.view');
    }

    public function view(User $user, Invoice $invoice)
    {
        return $user->can('invoices.view');
    }

    public function create(User $user)
    {
        return $user->can('invoices.manage');
    }

    public function delete(User $user, Invoice $invoice)
    {
        if (! $user->can('invoices.manage')) {
            return false;
        }

        if ((float) $invoice->amount_paid > 0) {
            return false;
        }

        return ! $invoice->payments()
            ->where('verification_status', \App\Models\Payment::STATUS_VERIFIED)
            ->exists();
    }
}

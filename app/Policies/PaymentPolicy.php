<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user)
    {
        return $user->can('payments.view');
    }

    public function create(User $user)
    {
        return $user->can('payments.view');
    }

    public function verify(User $user, Payment $payment)
    {
        return $user->can('payments.verify');
    }
}

<?php

namespace App\Policies;

use App\Models\SubscriptionPlan;
use App\Models\User;

class SubscriptionPlanPolicy
{
    public function viewAny(User $user)
    {
        return $user->can('subscriptions.view');
    }

    public function manage(User $user)
    {
        return $user->can('subscriptions.manage');
    }
}

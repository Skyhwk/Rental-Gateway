<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function viewAny(User $user)
    {
        return $user->can('subscriptions.view');
    }

    public function view(User $user, Subscription $subscription)
    {
        return $user->can('subscriptions.view');
    }

    public function create(User $user)
    {
        return $user->can('subscriptions.manage');
    }

    public function update(User $user, Subscription $subscription)
    {
        return $user->can('subscriptions.manage');
    }
}

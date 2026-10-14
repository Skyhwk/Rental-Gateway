<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClientPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->can('clients.view');
    }

    public function view(User $user, Client $client)
    {
        return $user->can('clients.view');
    }

    public function create(User $user)
    {
        return $user->can('clients.manage');
    }

    public function update(User $user, Client $client)
    {
        return $user->can('clients.manage');
    }

    public function delete(User $user, Client $client)
    {
        return $user->can('clients.manage');
    }
}

<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApplicationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->can('applications.view');
    }

    public function view(User $user, Application $application)
    {
        return $user->can('applications.view');
    }

    public function create(User $user)
    {
        return $user->can('applications.manage');
    }

    public function update(User $user, Application $application)
    {
        return $user->can('applications.manage');
    }

    public function delete(User $user, Application $application)
    {
        return $user->can('applications.manage');
    }
}

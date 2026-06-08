<?php

namespace App\Policies;

use App\Models\DashboardTask;
use App\Models\User;

class DashboardTaskPolicy
{
    public function update(User $user, DashboardTask $task): bool
    {
        return $user->role === 'coach' && $task->coach_id === $user->id;
    }
}

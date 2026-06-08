<?php

namespace App\Policies;

use App\Models\ProgramTemplate;
use App\Models\User;

class ProgramTemplatePolicy
{
    public function assign(User $user, ProgramTemplate $template): bool
    {
        return $user->role === 'coach' && $template->coach_id === $user->id;
    }
}

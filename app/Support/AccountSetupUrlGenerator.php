<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\URL;

class AccountSetupUrlGenerator
{
    public static function signedSetupUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'account.setup.show',
            now()->addDays(14),
            ['user' => $user->id],
        );
    }

    public static function signedUpdateUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'account.setup.update',
            now()->addDays(14),
            ['user' => $user->id],
        );
    }
}

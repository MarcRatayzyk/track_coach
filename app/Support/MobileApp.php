<?php

namespace App\Support;

use Illuminate\Http\Request;

class MobileApp
{
    public const USER_AGENT_MARKER = 'TrackCoachMobile/';

    /** @deprecated Kept for backward compatibility with older APK builds. */
    public const LEGACY_USER_AGENT_MARKER = 'TrackCoachAthlete/';

    public static function isRequest(Request $request): bool
    {
        $userAgent = $request->userAgent() ?? '';

        return str_contains($userAgent, self::USER_AGENT_MARKER)
            || str_contains($userAgent, self::LEGACY_USER_AGENT_MARKER);
    }
}

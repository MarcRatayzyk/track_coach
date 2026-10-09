<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthRedirect
{
    public static function homeRoute(User $user): string
    {
        return match ($user->role) {
            'admin' => 'admin.dashboard',
            'athlete' => 'athlete.dashboard',
            default => 'dashboard',
        };
    }

    public static function homeUrl(User $user): string
    {
        return route(self::homeRoute($user));
    }

    /**
     * Redirect after login, ignoring an intended URL that belongs to another role
     * (e.g. coach previously hitting /admin must not land on the admin panel).
     */
    public static function afterLogin(Request $request, User $user): RedirectResponse
    {
        $home = self::homeUrl($user);
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended) || $intended === '') {
            return redirect()->to($home);
        }

        if (! self::intendedAllowedFor($user, $intended)) {
            return redirect()->to($home);
        }

        return redirect()->to($intended);
    }

    public static function intendedAllowedFor(User $user, string $intended): bool
    {
        $path = parse_url($intended, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = $intended;
        }

        $path = '/'.ltrim($path, '/');
        $isAdminPath = Str::startsWith($path, '/admin');

        if ($user->role === 'admin') {
            return $isAdminPath;
        }

        if ($isAdminPath) {
            return false;
        }

        if ($user->role === 'coach' && Str::startsWith($path, '/athlete/')) {
            return false;
        }

        if ($user->role === 'athlete') {
            if ($user->isSelfCoached() && Str::startsWith($path, '/program-builder')) {
                return true;
            }

            if (
                $path === '/dashboard'
                || Str::startsWith($path, '/program-builder')
                || Str::startsWith($path, '/billing')
                || Str::startsWith($path, '/competitions')
            ) {
                return false;
            }
        }

        return true;
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ApkDownloadController extends Controller
{
    public const GITHUB_RELEASE_APK =
        'https://github.com/MarcRatayzyk/track_coach/releases/latest/download/power-roster.apk';

    public function __invoke(Request $request): BinaryFileResponse|RedirectResponse|Response
    {
        $candidates = [
            public_path('downloads/power-roster.apk'),
            storage_path('app/apks/power-roster.apk'),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && filesize($candidate) > 1024) {
                return response()->file($candidate, [
                    'Content-Type' => 'application/vnd.android.package-archive',
                    'Content-Disposition' => 'attachment; filename="power-roster.apk"',
                    'Cache-Control' => 'private, max-age=3600',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        $remote = config('trackcoach.android_apk_url') ?: self::GITHUB_RELEASE_APK;

        return redirect()->away($remote);
    }
}

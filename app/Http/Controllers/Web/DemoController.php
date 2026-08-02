<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDemoSandboxRequest;
use App\Mail\DemoWelcomeMail;
use App\Models\User;
use App\Services\DemoSandboxProvisioner;
use App\Support\MailSendSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DemoController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('DemoStartPage', [
            'demoHours' => (int) config('billing.demo_hours', 48),
        ]);
    }

    public function store(StoreDemoSandboxRequest $request, DemoSandboxProvisioner $provisioner): RedirectResponse
    {
        $validated = $request->validated();
        $hours = (int) config('billing.demo_hours', 48);

        $coach = User::query()->create([
            'name' => $validated['name'] ?? __('messages.auth.demo_coach_name'),
            'email' => $validated['email'],
            'password' => Str::password(48),
            'role' => 'coach',
            'is_demo' => true,
            'demo_expires_at' => now()->addHours($hours),
            'trial_ends_at' => null,
            'initial_setup_completed_at' => now(),
            'email_verified_at' => now(),
        ]);

        $provisioner->provision($coach);

        Auth::login($coach);
        $request->session()->regenerate();

        $expiresLabel = $coach->demo_expires_at
            ?->timezone(config('app.timezone'))
            ->format('d/m/Y H:i') ?? '';

        MailSendSupport::attempt(
            fn () => Mail::to($coach)->send(new DemoWelcomeMail(
                $coach,
                $hours,
                $expiresLabel,
                route('dashboard'),
            )),
        );

        return redirect()
            ->route('dashboard')
            ->with('success', __('messages.auth.demo_ready', ['hours' => $hours]))
            ->with('demo_welcome', [
                'hours' => $hours,
                'expires_label' => $expiresLabel,
                'email' => $coach->email,
            ]);
    }
}

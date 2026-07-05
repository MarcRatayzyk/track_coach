<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoachRegistrationRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('RegisterPage');
    }

    public function store(StoreCoachRegistrationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $coach = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'coach',
            'initial_setup_completed_at' => now(),
        ]);

        event(new Registered($coach));

        Auth::login($coach);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }
}

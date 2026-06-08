<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountSetupRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class AccountSetupController extends Controller
{
    public function show(Request $request, User $user): Response|RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Lien invalide ou expiré.');
        }

        if ($user->role !== 'athlete') {
            abort(404);
        }

        if ($user->initial_setup_completed_at !== null) {
            return redirect()
                ->route('login')
                ->with('success', 'Ce compte est déjà activé. Tu peux te connecter avec ton e-mail et ton mot de passe.');
        }

        return Inertia::render('AccountSetupPage', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'submitUrl' => URL::temporarySignedRoute(
                'account.setup.update',
                now()->addDays(14),
                ['user' => $user->id],
            ),
        ]);
    }

    public function update(StoreAccountSetupRequest $request, User $user): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Lien invalide ou expiré.');
        }

        if ($user->role !== 'athlete') {
            abort(404);
        }

        if ($user->initial_setup_completed_at !== null) {
            return redirect()
                ->route('login')
                ->with('success', 'Ce compte est déjà activé. Connecte-toi avec ton e-mail et ton mot de passe.');
        }

        $user->forceFill([
            'password' => $request->validated('password'),
            'initial_setup_completed_at' => now(),
        ])->save();

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'weight_class' => $request->validated('weight_class') ?: null,
                'bio' => $request->validated('bio') ?: null,
            ],
        );

        return redirect()
            ->route('login')
            ->with('success', 'Compte activé. Tu peux maintenant te connecter avec ton e-mail et ton mot de passe.');
    }
}

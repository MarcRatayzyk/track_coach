<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountSetupRequest;
use App\Http\Requests\StoreCoachAccountSetupRequest;
use App\Models\User;
use App\Support\AccountSetupUrlGenerator;
use App\Support\MailSendSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountSetupController extends Controller
{
    public function show(Request $request, User $user): Response|RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Lien invalide ou expiré.');
        }

        if (! in_array($user->role, ['coach', 'athlete'], true)) {
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
            'role' => $user->role,
            'submitUrl' => AccountSetupUrlGenerator::signedUpdateUrl($user),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Lien invalide ou expiré.');
        }

        if (! in_array($user->role, ['coach', 'athlete'], true)) {
            abort(404);
        }

        if ($user->initial_setup_completed_at !== null) {
            return redirect()
                ->route('login')
                ->with('success', 'Ce compte est déjà activé. Connecte-toi avec ton e-mail et ton mot de passe.');
        }

        $validated = $user->role === 'coach'
            ? $request->validate((new StoreCoachAccountSetupRequest)->rules())
            : $request->validate((new StoreAccountSetupRequest)->rules());

        $user->forceFill([
            'password' => $validated['password'],
            'initial_setup_completed_at' => now(),
        ])->save();

        if ($user->role === 'athlete') {
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'weight_class' => $validated['weight_class'] ?? null ?: null,
                    'bio' => $validated['bio'] ?? null ?: null,
                ],
            );
        }

        if ($user->role === 'coach') {
            $sent = MailSendSupport::attempt(
                fn () => $user->sendEmailVerificationNotification(),
            );

            return redirect()
                ->route('login')
                ->with(
                    'success',
                    $sent
                        ? 'Compte activé. Connecte-toi puis confirme ton e-mail pour accéder au dashboard.'
                        : 'Compte activé. Connecte-toi — si tu ne reçois pas l\'e-mail de confirmation, utilise « Renvoyer » sur la page de vérification.',
                );
        }

        $message = 'Compte activé. Tu peux maintenant te connecter avec ton e-mail et ton mot de passe.';

        return redirect()
            ->route('login')
            ->with('success', $message);
    }
}

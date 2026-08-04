<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\BillingAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', Rule::in(['coach', 'athlete', 'admin'])],
            'status' => ['nullable', Rule::in(['active', 'disabled'])],
        ]);

        $query = User::query()->latest('created_at');

        if (! empty($validated['q'])) {
            $term = '%'.$validated['q'].'%';
            $query->where(function ($builder) use ($term): void {
                $builder->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        if (! empty($validated['role'])) {
            $query->where('role', $validated['role']);
        }

        if (($validated['status'] ?? null) === 'disabled') {
            $query->whereNotNull('disabled_at');
        } elseif (($validated['status'] ?? null) === 'active') {
            $query->whereNull('disabled_at');
        }

        $users = $query
            ->paginate(20)
            ->withQueryString()
            ->through(function (User $user): array {
                $billingStatus = null;
                $athleteCount = null;

                if ($user->role === 'coach') {
                    $billingStatus = BillingAccess::status($user);
                    $athleteCount = $user->activeAthleteCount();
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->displayEmail() ?? $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at?->toIso8601String(),
                    'disabled_at' => $user->disabled_at?->toIso8601String(),
                    'trial_ends_at' => $user->trial_ends_at?->toIso8601String(),
                    'is_demo' => (bool) $user->is_demo,
                    'billing_status' => $billingStatus,
                    'athlete_count' => $athleteCount,
                ];
            });

        return Inertia::render('Admin/AdminUsersPage', [
            'users' => $users,
            'filters' => [
                'q' => $validated['q'] ?? '',
                'role' => $validated['role'] ?? '',
                'status' => $validated['status'] ?? '',
            ],
        ]);
    }

    public function toggleDisable(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Tu ne peux pas désactiver ton propre compte.');
        }

        if ($user->isDisabled()) {
            $user->forceFill(['disabled_at' => null])->save();

            return back()->with('success', 'Compte réactivé.');
        }

        $user->forceFill(['disabled_at' => now()])->save();

        return back()->with('success', 'Compte désactivé.');
    }

    public function extendTrial(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== 'coach') {
            return back()->with('error', 'Seuls les coachs ont un essai.');
        }

        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        $days = (int) ($validated['days'] ?? 14);
        $base = $user->trial_ends_at && $user->trial_ends_at->isFuture()
            ? $user->trial_ends_at->copy()
            : now();

        $user->forceFill([
            'is_demo' => false,
            'trial_ends_at' => $base->addDays($days),
            'disabled_at' => null,
        ])->save();

        return back()->with('success', "Essai prolongé de {$days} jours (jusqu’au {$user->trial_ends_at->toDateString()}).");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Tu ne peux pas supprimer ton propre compte.');
        }

        if ($user->role === 'admin') {
            $adminCount = User::query()->where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'Impossible de supprimer le dernier administrateur.');
            }
        }

        if ($user->role === 'coach' && $user->activeAthleteCount() > 0) {
            return back()->with('error', 'Ce coach a encore des athlètes actifs. Désactive le compte ou détache-les d’abord.');
        }

        try {
            DB::transaction(function () use ($user): void {
                if ($user->role === 'athlete') {
                    $user->coaches()->detach();
                }

                if ($user->role === 'coach') {
                    $user->athletes()->detach();
                }

                $user->tokens()->delete();
                $user->delete();
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Suppression impossible (dépendances liées). Désactive le compte à la place.');
        }

        return back()->with('success', 'Compte supprimé.');
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Actions\DeleteUserAccountAction;
use App\Actions\ExportUserDataAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AccountPrivacyController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('AccountPrivacyPage');
    }

    public function export(Request $request, ExportUserDataAction $export): JsonResponse
    {
        $data = $export->execute($request->user());

        $filename = 'power-roster-donnees-'.now()->format('Y-m-d').'.json';

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function destroy(Request $request, DeleteUserAccountAction $deleteAccount): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Mot de passe incorrect.',
            ]);
        }

        Auth::logout();
        $deleteAccount->execute($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('success', 'Ton compte et toutes tes données ont été supprimés.');
    }
}

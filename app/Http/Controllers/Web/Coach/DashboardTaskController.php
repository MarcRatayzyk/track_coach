<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Models\DashboardTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardTaskController extends Controller
{
    public function complete(Request $request, DashboardTask $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $task->update([
            'status' => 'done',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Retour marqué comme traité.');
    }
}

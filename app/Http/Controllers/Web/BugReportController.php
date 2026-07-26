<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBugReportRequest;
use App\Mail\BugReportMail;
use App\Support\MailSendSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class BugReportController extends Controller
{
    public function store(StoreBugReportRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $report = [
            'title' => $validated['title'],
            'category' => $validated['category'],
            'severity' => $validated['severity'],
            'description' => $validated['description'],
            'page_url' => $validated['page_url'] ?? null,
            'user_agent' => $request->userAgent(),
        ];

        $sent = MailSendSupport::attempt(
            fn () => Mail::to((string) config('trackcoach.support_email'))
                ->send(new BugReportMail(
                    reporter: $user,
                    report: $report,
                    screenshot: $request->file('screenshot'),
                )),
        );

        if (! $sent) {
            return back()->with('error', MailSendSupport::DELIVERY_FAILED_MESSAGE);
        }

        return back()->with('success', 'Merci — ton signalement a bien été envoyé.');
    }
}

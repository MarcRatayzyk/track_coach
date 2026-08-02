@component('mail.layout', ['subject' => __('mail.coach_invitation.layout_subject')])
<p style="margin:0 0 16px;color:#f8fafc;font-size:18px;font-weight:600;">{{ __('mail.coach_invitation.title') }}</p>
<p style="margin:0 0 16px;">{{ __('mail.coach_invitation.greeting', ['name' => $coach->name]) }}</p>
<p style="margin:0 0 16px;">{{ __('mail.coach_invitation.body') }}</p>
<p style="margin:0 0 24px;">
    <a href="{{ $setupUrl }}" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:12px;font-weight:600;">{{ __('mail.coach_invitation.cta') }}</a>
</p>
<p style="margin:0 0 8px;font-size:13px;color:#94a3b8;">{{ __('mail.coach_invitation.link_hint') }}</p>
<p style="margin:0;word-break:break-all;font-size:12px;color:#64748b;">{{ $setupUrl }}</p>
@endcomponent

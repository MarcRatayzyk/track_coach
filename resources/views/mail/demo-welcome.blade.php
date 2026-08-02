@component('mail.layout', ['subject' => __('mail.demo_welcome.layout_subject')])
<p style="margin:0 0 16px;color:#f8fafc;font-size:18px;font-weight:600;">{{ __('mail.demo_welcome.title') }}</p>
<p style="margin:0 0 16px;">{{ __('mail.demo_welcome.greeting', ['name' => $coach->name]) }}</p>
<p style="margin:0 0 16px;">{!! __('mail.demo_welcome.body', ['hours' => e((string) $demoHours), 'expires' => e($expiresLabel)]) !!}</p>
<p style="margin:0 0 16px;">{{ __('mail.demo_welcome.body_extra') }}</p>
<p style="margin:0 0 24px;">
    <a href="{{ $dashboardUrl }}" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:12px;font-weight:600;">{{ __('mail.demo_welcome.cta') }}</a>
</p>
<p style="margin:0;font-size:13px;color:#94a3b8;">{{ __('mail.demo_welcome.footer', ['email' => $coach->email]) }}</p>
@endcomponent

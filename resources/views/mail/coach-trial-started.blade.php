@component('mail.layout', ['subject' => __('mail.trial_started.layout_subject')])
<p style="margin:0 0 16px;color:#f8fafc;font-size:18px;font-weight:600;">{{ __('mail.trial_started.title') }}</p>
<p style="margin:0 0 16px;">{{ __('mail.trial_started.greeting', ['name' => $coach->name]) }}</p>
<p style="margin:0 0 16px;">{!! __('mail.trial_started.body', ['days' => e((string) $trialDays), 'ends' => e($trialEndsLabel)]) !!}</p>
<p style="margin:0 0 16px;">{{ __('mail.trial_started.body_extra') }}</p>
<p style="margin:0 0 24px;">
    <a href="{{ $dashboardUrl }}" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:12px;font-weight:600;">{{ __('mail.trial_started.cta') }}</a>
</p>
<p style="margin:0;font-size:13px;color:#94a3b8;">{{ __('mail.trial_started.footer') }}</p>
@endcomponent

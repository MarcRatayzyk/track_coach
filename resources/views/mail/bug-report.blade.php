@component('mail.layout', ['subject' => __('mail.bug_report.layout_subject')])
<p style="margin:0 0 16px;color:#f8fafc;font-size:18px;font-weight:600;">{{ __('mail.bug_report.title') }}</p>

<p style="margin:0 0 8px;"><strong style="color:#f8fafc;">{{ __('mail.bug_report.label_title') }}</strong> {{ $report['title'] }}</p>
<p style="margin:0 0 16px;"><strong style="color:#f8fafc;">{{ __('mail.bug_report.label_category') }}</strong> {{ $categoryLabel }}</p>

<p style="margin:0 0 8px;color:#f8fafc;font-weight:600;">{{ __('mail.bug_report.label_description') }}</p>
<p style="margin:0 0 20px;white-space:pre-wrap;">{{ $report['description'] }}</p>

<p style="margin:0 0 8px;color:#f8fafc;font-weight:600;">{{ __('mail.bug_report.label_user') }}</p>
<ul style="margin:0 0 20px;padding-left:18px;">
    <li>{{ __('mail.bug_report.label_id') }} {{ $reporter->id }}</li>
    <li>{{ __('mail.bug_report.label_name') }} {{ $reporter->name }}</li>
    <li>{{ __('mail.bug_report.label_email') }} {{ $reporter->email }}</li>
    <li>{{ __('mail.bug_report.label_role') }} {{ $reporter->role }}</li>
</ul>

<p style="margin:0 0 8px;color:#f8fafc;font-weight:600;">{{ __('mail.bug_report.label_context') }}</p>
<ul style="margin:0;padding-left:18px;">
    <li>{{ __('mail.bug_report.label_page') }} {{ $report['page_url'] ?: '—' }}</li>
    <li>{{ __('mail.bug_report.label_user_agent') }} {{ $report['user_agent'] ?: '—' }}</li>
    <li>{{ __('mail.bug_report.label_date') }} {{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</li>
</ul>
@endcomponent

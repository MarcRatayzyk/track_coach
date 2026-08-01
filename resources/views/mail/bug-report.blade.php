@component('mail.layout', ['subject' => 'Signalement Power Roster'])
<p style="margin:0 0 16px;color:#f8fafc;font-size:18px;font-weight:600;">Nouveau signalement</p>

<p style="margin:0 0 8px;"><strong style="color:#f8fafc;">Titre :</strong> {{ $report['title'] }}</p>
<p style="margin:0 0 16px;"><strong style="color:#f8fafc;">Catégorie :</strong> {{ $categoryLabel }}</p>

<p style="margin:0 0 8px;color:#f8fafc;font-weight:600;">Description</p>
<p style="margin:0 0 20px;white-space:pre-wrap;">{{ $report['description'] }}</p>

<p style="margin:0 0 8px;color:#f8fafc;font-weight:600;">Utilisateur</p>
<ul style="margin:0 0 20px;padding-left:18px;">
    <li>ID : {{ $reporter->id }}</li>
    <li>Nom : {{ $reporter->name }}</li>
    <li>E-mail : {{ $reporter->email }}</li>
    <li>Rôle : {{ $reporter->role }}</li>
</ul>

<p style="margin:0 0 8px;color:#f8fafc;font-weight:600;">Contexte</p>
<ul style="margin:0;padding-left:18px;">
    <li>Page : {{ $report['page_url'] ?: '—' }}</li>
    <li>User-Agent : {{ $report['user_agent'] ?: '—' }}</li>
    <li>Date : {{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</li>
</ul>
@endcomponent

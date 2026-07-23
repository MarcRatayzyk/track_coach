@component('mail.layout', ['subject' => 'Invitation athlète'])
<p style="margin:0 0 16px;color:#f8fafc;font-size:18px;font-weight:600;">Bienvenue sur Power Roster</p>
<p style="margin:0 0 16px;">Bonjour {{ $athlete->name }},</p>
<p style="margin:0 0 16px;"><strong>{{ $coach->name }}</strong> t’a ajouté à son groupe sur Power Roster. Active ton compte pour consulter ton programme, logger tes séances et envoyer tes retours vidéo.</p>
<p style="margin:0 0 24px;">
    <a href="{{ $setupUrl }}" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:12px;font-weight:600;">Activer mon compte</a>
</p>
<p style="margin:0 0 8px;font-size:13px;color:#94a3b8;">Ce lien est valable 14 jours. Si le bouton ne fonctionne pas, copie ce lien dans ton navigateur :</p>
<p style="margin:0;word-break:break-all;font-size:12px;color:#64748b;">{{ $setupUrl }}</p>
@endcomponent

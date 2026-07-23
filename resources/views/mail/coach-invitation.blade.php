@component('mail.layout', ['subject' => 'Invitation coach'])
<p style="margin:0 0 16px;color:#f8fafc;font-size:18px;font-weight:600;">Bienvenue sur Power Roster</p>
<p style="margin:0 0 16px;">Bonjour {{ $coach->name }},</p>
<p style="margin:0 0 16px;">Tu as été invité à rejoindre Power Roster en tant que coach. Clique sur le bouton ci-dessous pour choisir ton mot de passe et activer ton compte.</p>
<p style="margin:0 0 24px;">
    <a href="{{ $setupUrl }}" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:12px;font-weight:600;">Activer mon compte coach</a>
</p>
<p style="margin:0 0 8px;font-size:13px;color:#94a3b8;">Ce lien est valable 14 jours. Si le bouton ne fonctionne pas, copie ce lien dans ton navigateur :</p>
<p style="margin:0;word-break:break-all;font-size:12px;color:#64748b;">{{ $setupUrl }}</p>
@endcomponent

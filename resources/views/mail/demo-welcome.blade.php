@component('mail.layout', ['subject' => 'Sandbox démo'])
<p style="margin:0 0 16px;color:#f8fafc;font-size:18px;font-weight:600;">Sandbox démo prête</p>
<p style="margin:0 0 16px;">Bonjour {{ $coach->name }},</p>
<p style="margin:0 0 16px;">Ta sandbox démo Power Roster est active pendant <strong>{{ $demoHours }} heures</strong> (expire le <strong>{{ $expiresLabel }}</strong>).</p>
<p style="margin:0 0 16px;">Explore le dashboard, les programmes et les retours. Les données seront purgées à l’expiration. L’ajout d’athlètes n’est pas disponible en démo.</p>
<p style="margin:0 0 24px;">
    <a href="{{ $dashboardUrl }}" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:12px;font-weight:600;">Ouvrir la démo</a>
</p>
<p style="margin:0;font-size:13px;color:#94a3b8;">Tu es déjà connecté sur ton navigateur. Pour te reconnecter plus tard, utilise « Mot de passe oublié » avec {{ $coach->email }}.</p>
@endcomponent

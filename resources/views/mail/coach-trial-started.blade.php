@component('mail.layout', ['subject' => 'Essai gratuit'])
<p style="margin:0 0 16px;color:#f8fafc;font-size:18px;font-weight:600;">Essai gratuit activé</p>
<p style="margin:0 0 16px;">Bonjour {{ $coach->name }},</p>
<p style="margin:0 0 16px;">Bienvenue sur Power Roster. Ton essai gratuit de <strong>{{ $trialDays }} jours</strong> est actif jusqu’au <strong>{{ $trialEndsLabel }}</strong>.</p>
<p style="margin:0 0 16px;">Pendant cette période, tu peux inviter tes athlètes, construire des programmes et suivre leurs retours.</p>
<p style="margin:0 0 24px;">
    <a href="{{ $dashboardUrl }}" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:12px;font-weight:600;">Ouvrir mon dashboard</a>
</p>
<p style="margin:0;font-size:13px;color:#94a3b8;">Pense à confirmer ton e-mail si ce n’est pas déjà fait, pour accéder pleinement à l’application.</p>
@endcomponent

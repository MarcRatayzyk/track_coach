<?php

return [
    'salutation' => "Cordialement,\nPower Roster",

    'athlete_invitation' => [
        'subject' => ':name t’invite sur Power Roster',
        'layout_subject' => 'Invitation athlète',
        'title' => 'Bienvenue sur Power Roster',
        'greeting' => 'Bonjour :name,',
        'body' => '<strong>:coach</strong> t’a ajouté à son groupe sur Power Roster. Active ton compte pour consulter ton programme, logger tes séances et envoyer tes retours vidéo.',
        'cta' => 'Activer mon compte',
        'link_hint' => 'Ce lien est valable 14 jours. Si le bouton ne fonctionne pas, copie ce lien dans ton navigateur :',
    ],

    'coach_invitation' => [
        'subject' => 'Active ton compte coach Power Roster',
        'layout_subject' => 'Invitation coach',
        'title' => 'Bienvenue sur Power Roster',
        'greeting' => 'Bonjour :name,',
        'body' => 'Tu as été invité à rejoindre Power Roster en tant que coach. Clique sur le bouton ci-dessous pour choisir ton mot de passe et activer ton compte.',
        'cta' => 'Activer mon compte coach',
        'link_hint' => 'Ce lien est valable 14 jours. Si le bouton ne fonctionne pas, copie ce lien dans ton navigateur :',
    ],

    'trial_started' => [
        'subject' => 'Ton essai gratuit de :days jours a commencé',
        'layout_subject' => 'Essai gratuit',
        'title' => 'Essai gratuit activé',
        'greeting' => 'Bonjour :name,',
        'body' => 'Bienvenue sur Power Roster. Ton essai gratuit de <strong>:days jours</strong> est actif jusqu’au <strong>:ends</strong>.',
        'body_extra' => 'Pendant cette période, tu peux inviter tes athlètes, construire des programmes et suivre leurs retours.',
        'cta' => 'Ouvrir mon dashboard',
        'footer' => 'Pense à confirmer ton e-mail si ce n’est pas déjà fait, pour accéder pleinement à l’application.',
    ],

    'demo_welcome' => [
        'subject' => 'Ta démo Power Roster est prête',
        'layout_subject' => 'Démo',
        'title' => 'Démo prête',
        'greeting' => 'Bonjour :name,',
        'body' => 'Ta démo Power Roster est active pendant <strong>:hours heures</strong> (expire le <strong>:expires</strong>).',
        'body_extra' => 'Explore le dashboard, les programmes et les retours. Les données seront purgées à l’expiration. L’ajout d’athlètes n’est pas disponible en démo.',
        'cta' => 'Ouvrir la démo',
        'footer' => 'Tu es déjà connecté sur ton navigateur. Pour te reconnecter plus tard, utilise « Mot de passe oublié » avec :email.',
    ],

    'bug_report' => [
        'subject' => '[Power Roster] :category : :title',
        'layout_subject' => 'Signalement Power Roster',
        'title' => 'Nouveau signalement',
        'label_title' => 'Titre :',
        'label_category' => 'Catégorie :',
        'label_description' => 'Description',
        'label_user' => 'Utilisateur',
        'label_context' => 'Contexte',
        'label_id' => 'ID :',
        'label_name' => 'Nom :',
        'label_email' => 'E-mail :',
        'label_role' => 'Rôle :',
        'label_page' => 'Page :',
        'label_user_agent' => 'User-Agent :',
        'label_date' => 'Date :',
        'category_bug' => 'Bug',
        'category_fix' => 'Correctif',
        'category_idea' => 'Idée',
        'category_other' => 'Autre',
    ],

    'reset_password' => [
        'subject' => 'Réinitialise ton mot de passe Power Roster',
        'line1' => 'Tu reçois cet e-mail car nous avons reçu une demande de réinitialisation de mot de passe pour ton compte.',
        'action' => 'Réinitialiser le mot de passe',
        'expires' => 'Ce lien expirera dans :minutes minutes.',
        'line2' => 'Si tu n’as pas demandé de réinitialisation, ignore cet e-mail.',
    ],

    'verify_email' => [
        'subject' => 'Confirme ton adresse e-mail — Power Roster',
        'line1' => 'Merci de t’être inscrit sur Power Roster. Clique sur le bouton ci-dessous pour confirmer ton adresse e-mail et accéder à ton dashboard.',
        'action' => 'Confirmer mon e-mail',
        'line2' => 'Si tu n’as pas créé de compte, ignore cet e-mail.',
    ],

    'new_message' => [
        'subject' => 'Nouveau message de :name',
        'someone' => 'Quelqu’un',
        'line' => ':name t’a envoyé un message sur Power Roster.',
        'action' => 'Ouvrir la messagerie',
    ],

    'new_feedback' => [
        'subject' => 'Nouveau retour vidéo — :name',
        'an_athlete' => 'Un athlète',
        'line' => ':name a envoyé un retour vidéo pour la séance du :date.',
        'action' => 'Voir le retour',
    ],

    'feedback_replied' => [
        'subject' => ':name a répondu à ton retour',
        'your_coach' => 'Ton coach',
        'line' => ':name a répondu à ton retour vidéo.',
        'action' => 'Voir la réponse',
    ],
];

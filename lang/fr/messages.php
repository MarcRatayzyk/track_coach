<?php

return [
    'mail' => [
        'delivery_failed' => 'Impossible d\'envoyer l\'e-mail pour le moment. Réessaie dans quelques minutes.',
    ],

    'auth' => [
        'register_created_pay' => 'Compte créé. Finalise ton paiement pour activer l’abonnement.',
        'register_created_trial_invite' => 'Compte créé. Essai gratuit de :days jours activé. Invite tes athlètes par e-mail.',
        'register_created_trial_confirm' => 'Compte créé. Essai gratuit de :days jours activé. Confirme ton e-mail pour accéder au dashboard.',
        'email_confirmed' => 'E-mail confirmé. Bienvenue sur Power Roster !',
        'password_updated' => 'Mot de passe mis à jour. Tu peux te connecter.',
        'reset_link_invalid' => 'Ce lien de réinitialisation est invalide ou a expiré.',
        'forgot_password_sent' => 'Si un compte existe avec cet e-mail, tu recevras un lien de réinitialisation.',
        'account_already_active_login' => 'Ce compte est déjà activé. Tu peux te connecter avec ton e-mail et ton mot de passe.',
        'account_already_active_signin' => 'Ce compte est déjà activé. Connecte-toi avec ton e-mail et ton mot de passe.',
        'coach_activated_manual' => 'Compte activé. Connecte-toi avec ton e-mail et ton mot de passe.',
        'coach_activated_confirm_email' => 'Compte activé. Connecte-toi puis confirme ton e-mail pour accéder au dashboard.',
        'coach_activated_resend_hint' => 'Compte activé. Connecte-toi — si tu ne reçois pas l\'e-mail de confirmation, utilise « Renvoyer » sur la page de vérification.',
        'athlete_activated' => 'Compte activé. Tu peux maintenant te connecter avec ton e-mail et ton mot de passe.',
        'account_deleted' => 'Ton compte et toutes tes données ont été supprimés.',
        'demo_ready' => 'Démo prête — expire dans :hours h. Explore librement, les données seront purgées ensuite.',
        'link_invalid_or_expired' => 'Lien invalide ou expiré.',
        'demo_coach_name' => 'Coach démo',
    ],

    'sidebar' => [
        'coach_profile_subtitle' => 'Mon profil & stats roster',
        'my_coach' => 'Mon coach — :name',
        'view_profile' => 'Voir le profil',
    ],

    'billing' => [
        'demo_cannot_start_trial' => 'La démo ne peut pas démarrer un essai. Crée un vrai compte coach.',
        'demo_cannot_subscribe' => 'Crée un vrai compte coach pour t’abonner (la démo ne peut pas payer).',
        'demo_cannot_subscribe_long' => 'Les comptes démo ne peuvent pas s’abonner. Crée un vrai compte coach pour t’abonner, ou utilise l’essai 14 jours.',
        'stripe_not_configured' => 'Stripe n’est pas configuré. Renseigne STRIPE_KEY / STRIPE_SECRET et les price IDs.',
        'stripe_price_missing' => 'Price ID Stripe manquant pour ce plan (STRIPE_PRICE_*).',
        'subscription_updated' => 'Abonnement mis à jour.',
        'subscription_activated' => 'Abonnement activé. Bienvenue !',
        'no_stripe_customer' => 'Aucun client Stripe associé à ce compte.',
        'checkout_failed' => 'Impossible de démarrer le paiement Stripe. Réessaie ou contacte le support.',
        'trial_already_active' => 'Ton essai gratuit est déjà actif.',
        'trial_already_used' => 'Tu as déjà utilisé ton essai gratuit de :days jours. Choisis un abonnement pour continuer.',
        'already_subscribed' => 'Tu as déjà un abonnement actif.',
        'trial_cannot_start' => 'Impossible de démarrer un essai sur ce compte.',
        'trial_activated' => 'Essai gratuit de :days jours activé.',
        'plan_minimum_required' => 'Avec :count athlète(s), le plan minimum est « :plan ».',
    ],

    'athletes' => [
        'already_activated' => 'Ce compte est déjà activé.',
        'removed_from_group' => 'Athlète retiré de ton groupe. Son compte existe toujours pour une éventuelle réassociation.',
        'invitation_manual' => 'Athlète ajouté. Copie le lien d’activation et transmets-le à :name (WhatsApp, SMS…).',
        'invitation_email_sent' => 'Invitation envoyée par e-mail. Tu as aussi le lien d’activation ci-dessous à partager si besoin.',
        'invitation_email_failed' => 'Athlète ajouté, mais l’e-mail d’invitation n’a pas pu être envoyé. Partage le lien d’activation ci-dessous.',
        'resend_manual' => 'Lien d’activation régénéré pour :name. Copie-le et transmets-le à l’athlète.',
        'resend_email_sent' => 'Invitation renvoyée à :name.',
        'resend_email_failed' => 'Impossible de renvoyer l’e-mail. Partage le lien d’activation ci-dessous.',
        'profile_updated' => 'Profil athlète mis à jour.',
        'profile_updated_self' => 'Profil mis à jour.',
        'demo_cannot_add' => 'La démo ne permet pas d’ajouter d’athlètes. Crée un vrai compte pour gérer ton roster.',
        'cannot_add_with_subscription' => 'Impossible d’ajouter un athlète avec ton abonnement actuel.',
        'seat_limit_reached' => 'Tu as atteint la limite de :limit athlètes de ton plan. Passe à un plan supérieur.',
    ],

    'records' => [
        'pr_added' => 'PR ajouté.',
    ],

    'competitions' => [
        'added' => 'Compétition ajoutée.',
        'updated' => 'Compétition mise à jour.',
        'deleted' => 'Compétition supprimée.',
        'match_plan_saved' => 'Plan de match enregistré.',
    ],

    'sessions' => [
        'saved' => 'Séance enregistrée.',
        'updated' => 'Séance mise à jour.',
        'deleted' => 'Séance supprimée.',
        'pasted_one' => '1 séance collée.',
        'pasted_many' => ':count séances collées.',
        'imported_one' => '1 séance importée.',
        'imported_many' => ':count séances importées.',
        'cell_cleared' => 'Case vidée.',
    ],

    'programs' => [
        'block_created' => 'Bloc créé. Commencez à construire vos séances.',
        'block_deleted' => 'Bloc supprimé.',
        'block_duplicated' => 'Bloc dupliqué. Tu peux l\'ajuster puis l\'assigner.',
        'assigned_one' => 'Programme assigné à 1 athlète.',
        'assigned_many' => 'Programme assigné à :count athlètes.',
        'block_saved_assigned' => 'Bloc enregistré et assigné à l\'athlète.',
        'warmup_saved' => 'Échauffement du bloc enregistré.',
        'import_ai_too_long' => 'Réponse IA encore trop longue après découpage. Réessaie avec un PDF, ou utilise l’onglet JSON (IA externe).',
        'import_timeout' => 'Analyse trop longue (timeout PHP). Réessaie, ou importe un CSV/XLSX plutôt qu’une capture d’écran très lourde.',
    ],

    'feedback' => [
        'sent_to_coach' => 'Retour envoyé au coach.',
        'sent_to_athlete' => 'Retour envoyé à l’athlète.',
        'marked_done' => 'Retour marqué comme traité.',
        'write_before_send' => 'Écrivez votre retour avant de l’envoyer.',
        'annotation_deleted' => 'Annotation supprimée.',
        'week_from_to' => 'Semaine du :start au :end',
    ],

    'messaging' => [
        'conversation_opened' => 'Conversation ouverte.',
        'message_sent' => 'Message envoyé.',
    ],

    'readiness' => [
        'checkin_saved' => 'Check-in enregistré.',
        'default_form_updated' => 'Formulaire facteurs externes par défaut mis à jour.',
        'athlete_form_updated' => 'Formulaire facteurs externes de l\'athlète mis à jour.',
    ],

    'body_weight' => [
        'saved' => 'Poids du corps enregistré.',
    ],

    'coach' => [
        'profile_updated' => 'Profil coach mis à jour.',
    ],

    'exercises' => [
        'custom_added' => 'Exercice personnalisé ajouté.',
        'updated' => 'Exercice mis à jour.',
        'deleted' => 'Exercice supprimé.',
    ],

    'calendar' => [
        'reminder_added' => 'Rappel ajouté.',
        'reminder_updated' => 'Rappel mis à jour.',
        'reminder_deleted' => 'Rappel supprimé.',
        'athlete_not_in_roster' => 'Athlète hors roster.',
    ],

    'charts' => [
        'added_to_dashboard' => 'Graphique ajouté au tableau de bord.',
        'removed_from_dashboard' => 'Graphique retiré du tableau de bord.',
        'template_saved' => 'Modèle de graphique enregistré.',
        'template_updated' => 'Modèle de graphique mis à jour.',
        'template_deleted' => 'Modèle de graphique supprimé.',
    ],

    'day_table' => [
        'saved' => 'Tableau jour enregistré.',
        'updated' => 'Tableau jour mis à jour.',
        'deleted' => 'Tableau jour supprimé.',
        'keep_at_least_one' => 'Tu dois conserver au moins un tableau jour.',
    ],

    'video' => [
        'direct_upload_not_configured' => 'L’upload direct n’est pas configuré sur ce serveur.',
        'unsupported_format' => 'Format vidéo non pris en charge (MP4, MOV, WebM, 3GP…).',
        'cannot_finalize' => 'Cette vidéo ne peut plus être finalisée.',
        'file_not_found_retry' => 'Le fichier n’a pas été trouvé sur le stockage. Réessayez l’envoi.',
    ],

    'support' => [
        'bug_report_sent' => 'Merci — ton signalement a bien été envoyé.',
    ],

    'onboarding' => [
        'add_athlete_label' => 'Ajoute ton premier athlète',
        'add_athlete_description' => 'Invite un athlète pour commencer à le suivre.',
        'create_program_label' => 'Crée un premier programme',
        'create_program_description' => 'Construis un bloc d’entraînement dans le Program Builder.',
        'assign_program_label' => 'Assigne un programme actif',
        'assign_program_description' => 'Active un bloc pour l’un de tes athlètes.',
        'reply_feedback_label' => 'Réponds à un retour de séance',
        'reply_feedback_description' => 'Analyse une vidéo et renvoie tes conseils.',
    ],

    'validation' => [
        'email_unique_register' => 'Cette adresse e-mail est déjà utilisée. Connecte-toi : si tu n’as pas encore payé, tu pourras démarrer ton essai 14 jours depuis Abonnement.',
        'email_unique' => 'Cette adresse e-mail est déjà utilisée.',
        'email_unique_demo' => 'Cet e-mail est déjà utilisé. Connecte-toi, ou utilise une autre adresse pour la démo.',
        'email_required' => 'L’adresse e-mail est obligatoire.',
        'email_invalid' => 'L’adresse e-mail n’est pas valide.',
        'password_confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        'password_min' => 'Le mot de passe doit contenir au moins :min caractères.',
        'password_mixed' => 'Le mot de passe doit contenir au moins une majuscule et une minuscule.',
        'password_numbers' => 'Le mot de passe doit contenir au moins un chiffre.',
        'title_required' => 'Le titre est obligatoire.',
        'category_required' => 'Choisis une catégorie.',
        'category_invalid' => 'Catégorie invalide.',
        'description_required' => 'La description est obligatoire.',
        'description_max' => 'La description ne peut pas dépasser :max caractères.',
        'screenshot_image' => 'La capture doit être une image.',
        'screenshot_mimes' => 'Formats acceptés : JPEG, PNG ou WebP.',
        'screenshot_max' => 'La capture ne doit pas dépasser 4 Mo.',
        'feedback_frequency_required' => 'Choisis le type de coaching.',
        'feedback_frequency_in' => 'Choisis un type de coaching valide.',
        'feedback_content_or_video' => 'Ajoutez un message, des notes de séance ou au moins une vidéo.',
        'videos_invalid' => 'Une ou plusieurs vidéos sont invalides ou non finalisées.',
        'videos_max' => 'Vous pouvez envoyer au maximum :max vidéos.',
        'video_max_size' => 'Chaque vidéo ne doit pas dépasser 100 Mo.',
        'video_mimetypes' => 'Format vidéo non pris en charge (MP4, MOV, WebM, 3GP…).',
        'session_needs_content' => 'Ajoute au moins un exercice ou une note pour enregistrer la séance.',
        'load_numeric' => 'La charge doit être un nombre (ex. 140 ou 138,5).',
        'sets_integer' => 'Le nombre de séries doit être un entier.',
        'reps_integer' => 'Le nombre de reps doit être un entier.',
        'match_plan_scenario_required' => 'Ajoute au moins un scénario pour le plan structuré.',
        'scenario_name_required' => 'Le nom du scénario est requis.',
        'attempt_numeric' => 'L\'essai :n doit être un nombre.',
        'attempt_min' => 'L\'essai :n doit être supérieur ou égal à :min.',
        'attempt_max' => 'L\'essai :n doit être inférieur ou égal à :max.',
        'file_required_program' => 'Choisissez un fichier programme.',
        'file_required_photo' => 'Choisissez une photo ou un PDF.',
        'file_required_csv' => 'Choisissez un fichier CSV ou Excel.',
        'file_too_large' => 'Le fichier est trop volumineux.',
        'file_mimes_import' => 'Formats acceptés : CSV, XLSX, PDF ou image (JPG/PNG/WEBP).',
        'file_mimes_photo' => 'Formats acceptés : JPG, PNG, WEBP, GIF, PDF.',
        'file_mimes_mapped' => 'Formats acceptés : CSV (.csv, .txt) ou Excel (.xlsx).',
        'mapping_required' => 'Le mapping des colonnes est requis.',
        'json_required' => 'Colle le JSON du programme.',
        'json_too_large' => 'JSON trop volumineux (max ~2 Mo).',
        'json_invalid' => 'Fournis une chaîne JSON ou un objet.',
        'athlete_not_in_roster' => 'Cet athlète n’est pas dans votre roster.',
        'athletes_not_in_roster' => 'Un athlète sélectionné n’est pas dans votre roster.',
        'message_empty' => 'Le message ne peut pas être vide.',
        'message_or_audio' => 'Ajoutez un message texte ou au moins un fichier audio.',
        'cannot_reply_feedback' => 'Vous ne pouvez pas répondre à ce retour.',
        'day_table_columns' => 'Active au moins une colonne de prescription (séries, reps ou charge).',
        'ramp_steps_required' => 'Ajoute au moins un palier ramp valide (reps + charge).',
        'cluster_reps_required' => 'Indique le nombre de reps du cluster.',
        'cluster_duration_required' => 'Indique la durée du cluster en minutes.',
    ],
];

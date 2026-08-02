<?php

return [
    'mail' => [
        'delivery_failed' => 'Unable to send the email right now. Please try again in a few minutes.',
    ],

    'auth' => [
        'register_created_pay' => 'Account created. Complete payment to activate your subscription.',
        'register_created_trial_invite' => 'Account created. Free :days-day trial activated. Invite your athletes by email.',
        'register_created_trial_confirm' => 'Account created. Free :days-day trial activated. Confirm your email to access the dashboard.',
        'email_confirmed' => 'Email confirmed. Welcome to Power Roster!',
        'password_updated' => 'Password updated. You can log in.',
        'reset_link_invalid' => 'This reset link is invalid or has expired.',
        'forgot_password_sent' => 'If an account exists with this email, you will receive a reset link.',
        'account_already_active_login' => 'This account is already activated. You can log in with your email and password.',
        'account_already_active_signin' => 'This account is already activated. Sign in with your email and password.',
        'coach_activated_manual' => 'Account activated. Sign in with your email and password.',
        'coach_activated_confirm_email' => 'Account activated. Sign in then confirm your email to access the dashboard.',
        'coach_activated_resend_hint' => 'Account activated. Sign in — if you don’t get the confirmation email, use “Resend” on the verification page.',
        'athlete_activated' => 'Account activated. You can now log in with your email and password.',
        'account_deleted' => 'Your account and all your data have been deleted.',
        'demo_ready' => 'Demo ready — expires in :hours h. Explore freely; data will be purged afterwards.',
        'link_invalid_or_expired' => 'Invalid or expired link.',
        'demo_coach_name' => 'Demo coach',
    ],

    'sidebar' => [
        'coach_profile_subtitle' => 'My profile & roster stats',
        'my_coach' => 'My coach — :name',
        'view_profile' => 'View profile',
    ],

    'billing' => [
        'demo_cannot_start_trial' => 'The demo cannot start a trial. Create a real coach account.',
        'demo_cannot_subscribe' => 'Create a real coach account to subscribe (the demo cannot pay).',
        'demo_cannot_subscribe_long' => 'Demo accounts cannot subscribe. Create a real coach account to subscribe, or use the 14-day trial.',
        'stripe_not_configured' => 'Stripe is not configured. Set STRIPE_KEY / STRIPE_SECRET and the price IDs.',
        'stripe_price_missing' => 'Stripe price ID missing for this plan (STRIPE_PRICE_*).',
        'subscription_updated' => 'Subscription updated.',
        'subscription_activated' => 'Subscription activated. Welcome!',
        'no_stripe_customer' => 'No Stripe customer associated with this account.',
        'checkout_failed' => 'Unable to start Stripe checkout. Try again or contact support.',
        'trial_already_active' => 'Your free trial is already active.',
        'trial_already_used' => 'You already used your :days-day free trial. Choose a subscription to continue.',
        'already_subscribed' => 'You already have an active subscription.',
        'trial_cannot_start' => 'Unable to start a trial on this account.',
        'trial_activated' => ':days-day free trial activated.',
        'plan_minimum_required' => 'With :count athlete(s), the minimum plan is “:plan”.',
    ],

    'athletes' => [
        'already_activated' => 'This account is already activated.',
        'removed_from_group' => 'Athlete removed from your group. Their account still exists for a possible re-association.',
        'invitation_manual' => 'Athlete added. Copy the activation link and send it to :name (WhatsApp, SMS…).',
        'invitation_email_sent' => 'Invitation sent by email. You also have the activation link below to share if needed.',
        'invitation_email_failed' => 'Athlete added, but the invitation email could not be sent. Share the activation link below.',
        'resend_manual' => 'Activation link regenerated for :name. Copy it and send it to the athlete.',
        'resend_email_sent' => 'Invitation resent to :name.',
        'resend_email_failed' => 'Unable to resend the email. Share the activation link below.',
        'profile_updated' => 'Athlete profile updated.',
        'profile_updated_self' => 'Profile updated.',
        'demo_cannot_add' => 'The demo does not allow adding athletes. Create a real account to manage your roster.',
        'cannot_add_with_subscription' => 'Unable to add an athlete with your current subscription.',
        'seat_limit_reached' => 'You have reached the :limit athlete limit of your plan. Upgrade to a higher plan.',
    ],

    'records' => [
        'pr_added' => 'PR added.',
    ],

    'competitions' => [
        'added' => 'Competition added.',
        'updated' => 'Competition updated.',
        'deleted' => 'Competition deleted.',
        'match_plan_saved' => 'Match plan saved.',
    ],

    'sessions' => [
        'saved' => 'Session saved.',
        'updated' => 'Session updated.',
        'deleted' => 'Session deleted.',
        'pasted_one' => '1 session pasted.',
        'pasted_many' => ':count sessions pasted.',
        'imported_one' => '1 session imported.',
        'imported_many' => ':count sessions imported.',
        'cell_cleared' => 'Cell cleared.',
    ],

    'programs' => [
        'block_created' => 'Block created. Start building your sessions.',
        'block_deleted' => 'Block deleted.',
        'block_duplicated' => 'Block duplicated. You can adjust it then assign it.',
        'assigned_one' => 'Program assigned to 1 athlete.',
        'assigned_many' => 'Program assigned to :count athletes.',
        'block_saved_assigned' => 'Block saved and assigned to the athlete.',
        'warmup_saved' => 'Block warm-up saved.',
        'import_ai_too_long' => 'AI response still too long after splitting. Retry with a PDF, or use the JSON tab (external AI).',
        'import_timeout' => 'Analysis took too long (PHP timeout). Retry, or import a CSV/XLSX instead of a very heavy screenshot.',
    ],

    'feedback' => [
        'sent_to_coach' => 'Feedback sent to coach.',
        'sent_to_athlete' => 'Feedback sent to athlete.',
        'marked_done' => 'Feedback marked as done.',
        'write_before_send' => 'Write your feedback before sending.',
        'annotation_deleted' => 'Annotation deleted.',
        'week_from_to' => 'Week of :start to :end',
    ],

    'messaging' => [
        'conversation_opened' => 'Conversation opened.',
        'message_sent' => 'Message sent.',
    ],

    'readiness' => [
        'checkin_saved' => 'Check-in saved.',
        'default_form_updated' => 'Default external-factors form updated.',
        'athlete_form_updated' => 'Athlete external-factors form updated.',
    ],

    'body_weight' => [
        'saved' => 'Body weight saved.',
    ],

    'coach' => [
        'profile_updated' => 'Coach profile updated.',
    ],

    'exercises' => [
        'custom_added' => 'Custom exercise added.',
        'updated' => 'Exercise updated.',
        'deleted' => 'Exercise deleted.',
    ],

    'calendar' => [
        'reminder_added' => 'Reminder added.',
        'reminder_updated' => 'Reminder updated.',
        'reminder_deleted' => 'Reminder deleted.',
        'athlete_not_in_roster' => 'Athlete not in roster.',
    ],

    'charts' => [
        'added_to_dashboard' => 'Chart added to dashboard.',
        'removed_from_dashboard' => 'Chart removed from dashboard.',
        'template_saved' => 'Chart template saved.',
        'template_updated' => 'Chart template updated.',
        'template_deleted' => 'Chart template deleted.',
    ],

    'day_table' => [
        'saved' => 'Day table saved.',
        'updated' => 'Day table updated.',
        'deleted' => 'Day table deleted.',
        'keep_at_least_one' => 'You must keep at least one day table.',
    ],

    'video' => [
        'direct_upload_not_configured' => 'Direct upload is not configured on this server.',
        'unsupported_format' => 'Unsupported video format (MP4, MOV, WebM, 3GP…).',
        'cannot_finalize' => 'This video can no longer be finalized.',
        'file_not_found_retry' => 'The file was not found on storage. Please retry the upload.',
    ],

    'support' => [
        'bug_report_sent' => 'Thanks — your report was sent successfully.',
    ],

    'onboarding' => [
        'add_athlete_label' => 'Add your first athlete',
        'add_athlete_description' => 'Invite an athlete to start coaching them.',
        'create_program_label' => 'Create a first program',
        'create_program_description' => 'Build a training block in the Program Builder.',
        'assign_program_label' => 'Assign an active program',
        'assign_program_description' => 'Activate a block for one of your athletes.',
        'reply_feedback_label' => 'Reply to a session feedback',
        'reply_feedback_description' => 'Review a video and send your coaching notes.',
    ],

    'validation' => [
        'email_unique_register' => 'This email address is already in use. Sign in: if you haven’t paid yet, you can start your 14-day trial from Billing.',
        'email_unique' => 'This email address is already in use.',
        'email_unique_demo' => 'This email is already in use. Sign in, or use another address for the demo.',
        'email_required' => 'Email address is required.',
        'email_invalid' => 'The email address is not valid.',
        'password_confirmed' => 'The password confirmation does not match.',
        'password_min' => 'The password must be at least :min characters.',
        'password_mixed' => 'The password must contain at least one uppercase and one lowercase letter.',
        'password_numbers' => 'The password must contain at least one number.',
        'title_required' => 'Title is required.',
        'category_required' => 'Choose a category.',
        'category_invalid' => 'Invalid category.',
        'description_required' => 'Description is required.',
        'description_max' => 'Description may not be greater than :max characters.',
        'screenshot_image' => 'The screenshot must be an image.',
        'screenshot_mimes' => 'Accepted formats: JPEG, PNG or WebP.',
        'screenshot_max' => 'The screenshot must not exceed 4 MB.',
        'feedback_frequency_required' => 'Choose a coaching type.',
        'feedback_frequency_in' => 'Choose a valid coaching type.',
        'feedback_content_or_video' => 'Add a message, session notes, or at least one video.',
        'videos_invalid' => 'One or more videos are invalid or not finalized.',
        'videos_max' => 'You can upload a maximum of :max videos.',
        'video_max_size' => 'Each video must not exceed 100 MB.',
        'video_mimetypes' => 'Unsupported video format (MP4, MOV, WebM, 3GP…).',
        'session_needs_content' => 'Add at least one exercise or a note to save the session.',
        'load_numeric' => 'Load must be a number (e.g. 140 or 138.5).',
        'sets_integer' => 'Sets must be an integer.',
        'reps_integer' => 'Reps must be an integer.',
        'match_plan_scenario_required' => 'Add at least one scenario for the structured plan.',
        'scenario_name_required' => 'Scenario name is required.',
        'attempt_numeric' => 'Attempt :n must be a number.',
        'attempt_min' => 'Attempt :n must be greater than or equal to :min.',
        'attempt_max' => 'Attempt :n must be less than or equal to :max.',
        'file_required_program' => 'Choose a program file.',
        'file_required_photo' => 'Choose a photo or PDF.',
        'file_required_csv' => 'Choose a CSV or Excel file.',
        'file_too_large' => 'The file is too large.',
        'file_mimes_import' => 'Accepted formats: CSV, XLSX, PDF or image (JPG/PNG/WEBP).',
        'file_mimes_photo' => 'Accepted formats: JPG, PNG, WEBP, GIF, PDF.',
        'file_mimes_mapped' => 'Accepted formats: CSV (.csv, .txt) or Excel (.xlsx).',
        'mapping_required' => 'Column mapping is required.',
        'json_required' => 'Paste the program JSON.',
        'json_too_large' => 'JSON too large (max ~2 MB).',
        'json_invalid' => 'Provide a JSON string or object.',
        'athlete_not_in_roster' => 'This athlete is not in your roster.',
        'athletes_not_in_roster' => 'A selected athlete is not in your roster.',
        'message_empty' => 'The message cannot be empty.',
        'message_or_audio' => 'Add a text message or at least one audio file.',
        'cannot_reply_feedback' => 'You cannot reply to this feedback.',
        'day_table_columns' => 'Enable at least one prescription column (sets, reps or load).',
        'ramp_steps_required' => 'Add at least one valid ramp step (reps + load).',
        'cluster_reps_required' => 'Enter the number of cluster reps.',
        'cluster_duration_required' => 'Enter the cluster duration in minutes.',
    ],
];

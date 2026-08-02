<?php

return [
    'salutation' => "Best regards,\nPower Roster",

    'athlete_invitation' => [
        'subject' => ':name invites you to Power Roster',
        'layout_subject' => 'Athlete invitation',
        'title' => 'Welcome to Power Roster',
        'greeting' => 'Hello :name,',
        'body' => '<strong>:coach</strong> added you to their group on Power Roster. Activate your account to view your program, log sessions, and send video feedback.',
        'cta' => 'Activate my account',
        'link_hint' => 'This link is valid for 14 days. If the button doesn’t work, copy this link into your browser:',
    ],

    'coach_invitation' => [
        'subject' => 'Activate your Power Roster coach account',
        'layout_subject' => 'Coach invitation',
        'title' => 'Welcome to Power Roster',
        'greeting' => 'Hello :name,',
        'body' => 'You have been invited to join Power Roster as a coach. Click the button below to choose your password and activate your account.',
        'cta' => 'Activate my coach account',
        'link_hint' => 'This link is valid for 14 days. If the button doesn’t work, copy this link into your browser:',
    ],

    'trial_started' => [
        'subject' => 'Your :days-day free trial has started',
        'layout_subject' => 'Free trial',
        'title' => 'Free trial activated',
        'greeting' => 'Hello :name,',
        'body' => 'Welcome to Power Roster. Your <strong>:days-day</strong> free trial is active until <strong>:ends</strong>.',
        'body_extra' => 'During this period, you can invite athletes, build programs, and follow their feedback.',
        'cta' => 'Open my dashboard',
        'footer' => 'Please confirm your email if you haven’t already, to fully access the app.',
    ],

    'demo_welcome' => [
        'subject' => 'Your Power Roster demo is ready',
        'layout_subject' => 'Demo',
        'title' => 'Demo ready',
        'greeting' => 'Hello :name,',
        'body' => 'Your Power Roster demo is active for <strong>:hours hours</strong> (expires on <strong>:expires</strong>).',
        'body_extra' => 'Explore the dashboard, programs, and feedback. Data will be purged on expiration. Adding athletes is not available in the demo.',
        'cta' => 'Open the demo',
        'footer' => 'You are already signed in on your browser. To sign in again later, use “Forgot password” with :email.',
    ],

    'bug_report' => [
        'subject' => '[Power Roster] :category : :title',
        'layout_subject' => 'Power Roster report',
        'title' => 'New report',
        'label_title' => 'Title:',
        'label_category' => 'Category:',
        'label_description' => 'Description',
        'label_user' => 'User',
        'label_context' => 'Context',
        'label_id' => 'ID:',
        'label_name' => 'Name:',
        'label_email' => 'Email:',
        'label_role' => 'Role:',
        'label_page' => 'Page:',
        'label_user_agent' => 'User-Agent:',
        'label_date' => 'Date:',
        'category_bug' => 'Bug',
        'category_fix' => 'Fix',
        'category_idea' => 'Idea',
        'category_other' => 'Other',
    ],

    'reset_password' => [
        'subject' => 'Reset your Power Roster password',
        'line1' => 'You are receiving this email because we received a password reset request for your account.',
        'action' => 'Reset password',
        'expires' => 'This link will expire in :minutes minutes.',
        'line2' => 'If you did not request a reset, ignore this email.',
    ],

    'verify_email' => [
        'subject' => 'Confirm your email address — Power Roster',
        'line1' => 'Thanks for signing up to Power Roster. Click the button below to confirm your email address and access your dashboard.',
        'action' => 'Confirm my email',
        'line2' => 'If you did not create an account, ignore this email.',
    ],

    'new_message' => [
        'subject' => 'New message from :name',
        'someone' => 'Someone',
        'line' => ':name sent you a message on Power Roster.',
        'action' => 'Open messaging',
    ],

    'new_feedback' => [
        'subject' => 'New video feedback — :name',
        'an_athlete' => 'An athlete',
        'line' => ':name sent video feedback for the session on :date.',
        'action' => 'View feedback',
    ],

    'feedback_replied' => [
        'subject' => ':name replied to your feedback',
        'your_coach' => 'Your coach',
        'line' => ':name replied to your video feedback.',
        'action' => 'View reply',
    ],
];

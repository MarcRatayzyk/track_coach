<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trial & demo
    |--------------------------------------------------------------------------
    */

    'trial_days' => (int) env('BILLING_TRIAL_DAYS', 14),

    /** Max active athletes during the free trial (Starter tier). */
    'trial_max_athletes' => (int) env('BILLING_TRIAL_MAX_ATHLETES', 15),

    'demo_hours' => (int) env('BILLING_DEMO_HOURS', 48),

    /*
    |--------------------------------------------------------------------------
    | Subscription plans (monthly, EUR)
    |--------------------------------------------------------------------------
    |
    | Create matching Products / Prices in the Stripe Dashboard and set the
    | price IDs below. max_athletes = null means unlimited.
    |
    */

    'plans' => [
        'starter' => [
            'key' => 'starter',
            'name' => 'Starter',
            'price_eur' => 34.99,
            'max_athletes' => 15,
            'description' => 'Jusqu’à 15 athlètes',
            'price_id' => env('STRIPE_PRICE_STARTER'),
        ],
        'growth' => [
            'key' => 'growth',
            'name' => 'Growth',
            'price_eur' => 49.99,
            'max_athletes' => 40,
            'description' => 'De 16 à 40 athlètes',
            'price_id' => env('STRIPE_PRICE_GROWTH'),
        ],
        'scale' => [
            'key' => 'scale',
            'name' => 'Scale',
            'price_eur' => 74.99,
            'max_athletes' => null,
            'description' => '41 athlètes et plus',
            'price_id' => env('STRIPE_PRICE_SCALE'),
        ],
    ],

];

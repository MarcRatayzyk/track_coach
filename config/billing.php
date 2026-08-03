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
    | Launch promo (display)
    |--------------------------------------------------------------------------
    |
    | Applied on marketing / billing UI. Stripe Price IDs still control the
    | amount actually charged at checkout.
    |
    */

    'launch_discount_percent' => (int) env('BILLING_LAUNCH_DISCOUNT_PERCENT', 50),

    /*
    |--------------------------------------------------------------------------
    | Subscription plans (monthly)
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
            'price_eur' => 39.99,
            'price_usd' => 49.99,
            'max_athletes' => 15,
            'description' => 'Up to 15 athletes',
            'price_id' => env('STRIPE_PRICE_STARTER'),
        ],
        'growth' => [
            'key' => 'growth',
            'name' => 'Growth',
            'price_eur' => 59.99,
            'price_usd' => 69.99,
            'max_athletes' => 40,
            'description' => '16 to 40 athletes',
            'price_id' => env('STRIPE_PRICE_GROWTH'),
        ],
        'scale' => [
            'key' => 'scale',
            'name' => 'Scale',
            'price_eur' => 79.99,
            'price_usd' => 89.99,
            'max_athletes' => null,
            'description' => '41+ athletes',
            'price_id' => env('STRIPE_PRICE_SCALE'),
        ],
    ],

];

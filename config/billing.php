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

    /**
     * How many USD equal 1 EUR for display (USD → EUR = amount / rate).
     * Default 1.08 ≈ mid-market. Override with BILLING_EUR_TO_USD_RATE.
     */
    'eur_to_usd_rate' => (float) env('BILLING_EUR_TO_USD_RATE', 1.08),

    /*
    |--------------------------------------------------------------------------
    | Subscription plans (monthly)
    |--------------------------------------------------------------------------
    |
    | Catalogue prices are defined in USD. EUR display = USD ÷ eur_to_usd_rate.
    | With -50% launch: USD 24.99 / 34.99 / 44.99 (from list 49.99 / 69.99 / 89.99).
    |
    */

    'plans' => [
        'starter' => [
            'key' => 'starter',
            'name' => 'Starter',
            'price_usd' => 49.99,
            'max_athletes' => 15,
            'description' => 'Up to 15 athletes',
            'price_id' => env('STRIPE_PRICE_STARTER'),
        ],
        'growth' => [
            'key' => 'growth',
            'name' => 'Growth',
            'price_usd' => 69.99,
            'max_athletes' => 40,
            'description' => '16 to 40 athletes',
            'price_id' => env('STRIPE_PRICE_GROWTH'),
        ],
        'scale' => [
            'key' => 'scale',
            'name' => 'Scale',
            'price_usd' => 89.99,
            'max_athletes' => null,
            'description' => '41+ athletes',
            'price_id' => env('STRIPE_PRICE_SCALE'),
        ],
    ],

];

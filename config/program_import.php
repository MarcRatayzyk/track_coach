<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vision / LLM for photo & PDF imports
    |--------------------------------------------------------------------------
    |
    | When PROGRAM_IMPORT_OPENAI_API_KEY is set, coaches can upload photos or
    | PDFs of existing programs. The same review screen as CSV is used.
    |
    */
    'openai_api_key' => env('PROGRAM_IMPORT_OPENAI_API_KEY', env('OPENAI_API_KEY')),
    'openai_model' => env('PROGRAM_IMPORT_OPENAI_MODEL', 'gpt-4o'),
    'openai_endpoint' => env('PROGRAM_IMPORT_OPENAI_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
    /*
     | Sur Windows local, si cURL error 60 (SSL), soit corriger php.ini curl.cainfo,
     | soit mettre PROGRAM_IMPORT_HTTP_VERIFY=false (dev uniquement).
     */
    'http_verify' => filter_var(env('PROGRAM_IMPORT_HTTP_VERIFY', true), FILTER_VALIDATE_BOOLEAN),
    'max_photo_bytes' => (int) env('PROGRAM_IMPORT_MAX_PHOTO_BYTES', 8 * 1024 * 1024),
    'max_csv_bytes' => (int) env('PROGRAM_IMPORT_MAX_CSV_BYTES', 5 * 1024 * 1024),
    'max_tokens' => (int) env('PROGRAM_IMPORT_MAX_TOKENS', 16384),
    'timeout_seconds' => (int) env('PROGRAM_IMPORT_TIMEOUT', 180),
    'weeks_per_batch' => (int) env('PROGRAM_IMPORT_WEEKS_PER_BATCH', 1),
    'verify_numbers' => filter_var(env('PROGRAM_IMPORT_VERIFY_NUMBERS', true), FILTER_VALIDATE_BOOLEAN),
    'max_source_chars' => (int) env('PROGRAM_IMPORT_MAX_SOURCE_CHARS', 200000),
    /** Retries with backoff when OpenAI returns 429 Too Many Requests. */
    'rate_limit_retries' => (int) env('PROGRAM_IMPORT_RATE_LIMIT_RETRIES', 4),
];

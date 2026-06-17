<?php

return [

    /*
    |--------------------------------------------------------------------------
    | XFusion-llm (FastAPI) — knowledge vector sync
    |--------------------------------------------------------------------------
    */

    'api_url' => rtrim((string) env('XFUSION_LLM_API_URL', 'http://127.0.0.1:8000'), '/'),

    'api_key' => env('XFUSION_LLM_API_KEY'),

    /** When false, Laravel still saves wp_posts but skips HTTP calls to FastAPI. */
    'sync_enabled' => (bool) env('XFUSION_LLM_SYNC_ENABLED', true),

    'timeout_seconds' => (int) env('XFUSION_LLM_TIMEOUT', 60),

    /**
     * Suggested categories — must match exam evaluation category names when possible.
     */
    'categories' => [
        'Customer Service',
        'Standard Operating Procedure',
        'Exam Evaluation',
        'Employee Training',
        'Get Real',
        'Fill Buckets',
        'Be Intentional',
        'Foster Grit',
        'Drive Growth',
        'Company Information',
        'COR Performance',
    ],

];

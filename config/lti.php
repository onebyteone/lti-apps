<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LTI 1.1 Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for LTI 1.1 Basic integration with Canvas LMS
    |
    */

    // Chatbot Tool
    'consumer_key' => env('LTI_CONSUMER_KEY'),
    'shared_secret' => env('LTI_SHARED_SECRET'),

    // Auto-Grading Tool
    'grading_consumer_key' => env('LTI_GRADING_CONSUMER_KEY', env('LTI_CONSUMER_KEY')),
    'grading_shared_secret' => env('LTI_GRADING_SHARED_SECRET', env('LTI_SHARED_SECRET')),

];


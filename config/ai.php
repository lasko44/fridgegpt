<?php

return [
    'openai_api_key' => env('OPENAI_API_KEY'),

    'embedding' => [
        'model' => env('EMBEDDING_MODEL', 'text-embedding-3-small'),
        'dimensions' => (int) env('EMBEDDING_DIMENSIONS', 1536),
    ],
];

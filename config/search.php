<?php

return [
    // Minimum lexical score for an article to appear in search results.
    'search_min_score' => 0.08,
    'search_limit' => 15,
    // A follow-up message that ranks another article at least this high starts a new topic.
    'new_topic_min_score' => 0.25,
    'max_question_chars' => 2000,

    // Embedder container. Search must keep working when it is down.
    'embedder_url' => env('EMBEDDER_URL', 'http://embedder:8000'),
    // Seconds allowed for a single query embedding.
    'embedder_timeout' => (float) env('EMBEDDER_TIMEOUT', 1.5),
    // Seconds allowed for a batch of passages (used by search:embed).
    'embedder_batch_timeout' => (float) env('EMBEDDER_BATCH_TIMEOUT', 120),
    'embedder_model' => env('EMBEDDER_MODEL', 'intfloat/multilingual-e5-small'),
    'embedder_dimension' => 384,
];

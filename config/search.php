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

    // Blended ranking is optional and off: on the holdout split it did not beat lexical (top-1 0.3099 against
    // 0.3521, top-3 0.5211 against 0.5352). search:eval and search:tune can still measure it while it is off.
    //
    // final = lexical_weight * lexical + w * semantic, where w is semantic_weight_with_concept
    // when the question hit a dictionary concept and semantic_weight otherwise. Used instead of the lexical
    // thresholds above only while enabled and the embedder and stored vectors work. Values from `search:tune` (split=tune only).
    //
    // Semantic is each article's best cosine mapped linearly: (cos - cos_floor) / (cos_ceil - cos_floor), clamped
    // to 0..1. e5-small puts nearly everything near 0.8, hence the narrow band. Per-query z-scores were tried on
    // the tune split and did worse (best top-1 72/146, top-3 98/146, against 75/146 and 103/146), so they are not used.
    'blend' => [
        'enabled' => (bool) env('SEARCH_BLEND_ENABLED', false),
        'lexical_weight' => 0.6,
        'semantic_weight' => 0.4,
        'semantic_weight_with_concept' => 0.1,
        'cos_floor' => 0.75,
        'cos_ceil' => 0.9,
        'min_score' => 0.28,
        // The eval metrics ignore "ambiguous", so this is the margin where the status best predicts a right top article.
        'margin' => 0.11,
        // Highest threshold that lists the expected article as often as the lexical setting does.
        'search_min_score' => 0.19,
        // min_score * 1.25, the same ratio as the lexical settings.
        'new_topic_min_score' => 0.35,
    ],
];

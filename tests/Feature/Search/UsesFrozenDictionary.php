<?php

namespace Tests\Feature\Search;

use App\Search\LexicalScorer;

/** Pins the scorer to the frozen copy of the dictionary, so golden parity keeps proving the port is faithful. */
trait UsesFrozenDictionary
{
    private function useFrozenDictionary(): void
    {
        $this->app->instance(LexicalScorer::class, new LexicalScorer(base_path('tests/fixtures/concepts-golden.json')));
    }
}

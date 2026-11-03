<?php

namespace Tests\Feature\Search;

use App\Models\LawEmbedding;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/** Runs the real blend offline: stored passage vectors and query vectors recorded from the real embedder. */
trait UsesCachedVectors
{
    private function useCachedVectors(): void
    {
        $model = config('search.embedder_model');
        $fixtures = base_path('tests/fixtures');

        $passages = json_decode((string) file_get_contents("{$fixtures}/passage-vectors.json"), true, flags: JSON_THROW_ON_ERROR);
        foreach (array_chunk($passages, 20) as $chunk) {
            LawEmbedding::insert(array_map(fn (array $p): array => [
                'dieu_luat_id' => $p['dieu_luat_id'],
                'so_khoan' => $p['so_khoan'],
                'model' => $model,
                'content_hash' => str_repeat('a', 64),
                'vector' => json_encode($p['vector']),
            ], $chunk));
        }

        $queries = json_decode((string) file_get_contents("{$fixtures}/query-vectors.json"), true, flags: JSON_THROW_ON_ERROR);
        Http::fake(['*/embed' => fn (Request $request) => Http::response([
            'model' => $model,
            'dimension' => 384,
            'vectors' => array_map(
                fn (string $text): array => $queries[$text] ?? throw new \RuntimeException("No recorded vector for: {$text}"),
                $request['texts'],
            ),
        ])]);
    }
}

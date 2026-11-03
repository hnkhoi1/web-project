<?php

namespace App\Search;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/** HTTP client for the embedder container. Every failure yields null, never an exception. */
class EmbeddingClient
{
    /** @return list<float>|null */
    public function embedQuery(string $text): ?array
    {
        $vectors = $this->embed([$text], 'query', (float) config('search.embedder_timeout'));

        return $vectors[0] ?? null;
    }

    /**
     * @param  list<string>  $texts
     * @return list<list<float>>|null
     */
    public function embedPassages(array $texts): ?array
    {
        return $this->embed($texts, 'passage', (float) config('search.embedder_batch_timeout'));
    }

    /** True when the embedder answers its health check. */
    public function healthy(): bool
    {
        try {
            return Http::timeout(2)->get($this->url('/health'))->successful();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  list<string>  $texts
     * @return list<list<float>>|null
     */
    private function embed(array $texts, string $kind, float $timeout): ?array
    {
        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->post($this->url('/embed'), ['texts' => array_values($texts), 'kind' => $kind]);

            if (! $response->successful()) {
                return $this->fail("HTTP {$response->status()}");
            }

            $body = $response->json();
            $dimension = (int) config('search.embedder_dimension');

            if (! is_array($body) || ($body['model'] ?? null) !== config('search.embedder_model')) {
                return $this->fail('model mismatch');
            }

            $vectors = $body['vectors'] ?? null;
            if (! is_array($vectors) || count($vectors) !== count($texts)) {
                return $this->fail('unexpected vector count');
            }

            $out = [];
            foreach ($vectors as $vector) {
                if (! is_array($vector) || count($vector) !== $dimension) {
                    return $this->fail('wrong dimension');
                }
                $out[] = array_map(floatval(...), array_values($vector));
            }

            return $out;
        } catch (Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    private function fail(string $reason): null
    {
        Log::warning("Embedder request failed: {$reason}");

        return null;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('search.embedder_url'), '/').$path;
    }
}

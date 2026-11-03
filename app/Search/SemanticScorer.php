<?php

namespace App\Search;

/** Scores articles by how close their stored vectors are to a query vector. */
class SemanticScorer
{
    /**
     * Each article's best cosine over its vectors, mapped onto 0..1 between blend.cos_floor and blend.cos_ceil.
     *
     * @param  list<float>  $queryVector
     * @param  array<int, list<list<float>>>  $vectorsByArticle
     * @return array<int, float>
     */
    public function scores(array $queryVector, array $vectorsByArticle): array
    {
        return $this->scale($this->cosines($queryVector, $vectorsByArticle));
    }

    /**
     * @param  list<float>  $queryVector
     * @param  array<int, list<list<float>>>  $vectorsByArticle
     * @return array<int, float> Each article's best cosine.
     */
    public function cosines(array $queryVector, array $vectorsByArticle): array
    {
        $queryNorm = $this->norm($queryVector);
        $out = [];
        foreach ($vectorsByArticle as $id => $vectors) {
            $best = -1.0;
            foreach ($vectors as $vector) {
                $norm = $this->norm($vector);
                if ($queryNorm > 0.0 && $norm > 0.0) {
                    $best = max($best, $this->dot($queryVector, $vector) / ($queryNorm * $norm));
                }
            }
            $out[$id] = $best;
        }

        return $out;
    }

    /**
     * @param  array<int, float>  $cosines
     * @return array<int, float>
     */
    public function scale(array $cosines): array
    {
        $floor = (float) config('search.blend.cos_floor');
        $ceil = (float) config('search.blend.cos_ceil');

        return array_map(fn (float $cos): float => max(0.0, min(1.0, ($cos - $floor) / ($ceil - $floor))), $cosines);
    }

    /** @param  list<float>  $a */
    private function norm(array $a): float
    {
        return sqrt($this->dot($a, $a));
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    private function dot(array $a, array $b): float
    {
        $sum = 0.0;
        foreach ($a as $i => $value) {
            $sum += $value * $b[$i];
        }

        return $sum;
    }
}

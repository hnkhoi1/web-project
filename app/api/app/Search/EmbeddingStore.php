<?php

namespace App\Search;

use App\Models\LawEmbedding;

class EmbeddingStore
{
    /**
     * Article id to all of its vectors (the article's own plus one per clause).
     *
     * @return array<int, list<list<float>>>
     */
    public function vectorsFor(string $model): array
    {
        $out = [];

        LawEmbedding::query()
            ->where('model', $model)
            ->orderBy('dieu_luat_id')
            ->orderBy('so_khoan')
            ->cursor()
            ->each(function (LawEmbedding $row) use (&$out): void {
                $out[(int) $row->dieu_luat_id][] = array_map(floatval(...), $row->vector);
            });

        return $out;
    }
}

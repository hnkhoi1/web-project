<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LawEmbedding extends Model
{
    /** so_khoan value of an article-level vector. */
    public const ARTICLE = 0;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'so_khoan' => 'integer',
            'vector' => 'array',
        ];
    }
}

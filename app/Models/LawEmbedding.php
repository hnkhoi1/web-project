<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LawEmbedding extends Model
{
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

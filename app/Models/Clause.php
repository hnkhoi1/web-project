<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Clause extends Model
{
    protected $table = 'khoan';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'so_khoan' => 'integer',
            'ti_le_ton_thuong_min' => 'float',
            'ti_le_ton_thuong_max' => 'float',
            'gia_tri_tai_san_min' => 'float',
            'gia_tri_tai_san_max' => 'float',
            'so_nguoi_chet_min' => 'integer',
            'so_nguoi_chet_max' => 'integer',
        ];
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'dieu_luat_id');
    }
}

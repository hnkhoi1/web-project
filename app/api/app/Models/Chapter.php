<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ten_chuong', 'so_thu_tu', 'mo_ta'])]
class Chapter extends Model
{
    protected $table = 'chuong';

    public $timestamps = false;

    /** @return HasMany<Article, $this> */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'chuong_id');
    }
}

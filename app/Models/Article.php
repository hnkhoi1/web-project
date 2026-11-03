<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    protected $table = 'dieu_luat';

    /** The id is the article number, assigned by the seeder. */
    public $incrementing = false;

    protected $guarded = [];

    /** @return BelongsTo<Chapter, $this> */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class, 'chuong_id');
    }

    /** @return HasMany<Clause, $this> */
    public function clauses(): HasMany
    {
        return $this->hasMany(Clause::class, 'dieu_luat_id')->orderBy('so_khoan');
    }
}

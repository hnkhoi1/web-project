<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'content', 'color', 'pinned', 'source_type', 'source_ref'])]
class Note extends Model
{
    public const COLORS = ['yellow', 'green', 'blue', 'rose', 'violet', 'gray'];

    protected function casts(): array
    {
        return ['pinned' => 'boolean'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

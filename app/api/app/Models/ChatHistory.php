<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'cau_hoi', 'dieu_luat_id', 'khoan_so', 'diem'])]
class ChatHistory extends Model
{
    protected $table = 'lich_su_hoi_dap';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['diem' => 'float'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'dieu_luat_id');
    }
}

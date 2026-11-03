<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'theme', 'font', 'note_color'])]
class UserPref extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    public const CREATED_AT = null;

    public const THEMES = ['system', 'light', 'dark'];

    public const FONTS = ['sm', 'md', 'lg', 'xl'];

    /**
     * Preferences as the frontend sees them, falling back to the defaults.
     *
     * @return array{theme: string, font: string, noteColor: string}
     */
    public static function payloadFor(?User $user): array
    {
        $prefs = $user?->prefs;

        return [
            'theme' => $prefs->theme ?? 'system',
            'font' => $prefs->font ?? 'md',
            'noteColor' => $prefs->note_color ?? 'yellow',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Http\Requests\Settings;

use App\Models\Note;
use App\Models\UserPref;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreferencesRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'theme' => ['required', Rule::in(UserPref::THEMES)],
            'font' => ['required', Rule::in(UserPref::FONTS)],
            'noteColor' => ['required', Rule::in(Note::COLORS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'theme.*' => 'Giao diện không hợp lệ.',
            'font.*' => 'Cỡ chữ không hợp lệ.',
            'noteColor.*' => 'Màu ghi chú không hợp lệ.',
        ];
    }
}

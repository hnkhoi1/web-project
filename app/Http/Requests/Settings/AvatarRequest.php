<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AvatarRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => [
                'bail',
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:min_width=64,min_height=64',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.required' => 'Vui lòng chọn một ảnh.',
            'avatar.file' => 'Vui lòng chọn một tệp ảnh hợp lệ.',
            'avatar.uploaded' => 'Không tải được ảnh lên. Ảnh có thể quá lớn (tối đa 2 MB).',
            'avatar.mimes' => 'Ảnh đại diện phải là tệp JPG, PNG hoặc WebP.',
            'avatar.max' => 'Ảnh đại diện không được vượt quá 2 MB.',
            'avatar.dimensions' => 'Ảnh đại diện phải có kích thước tối thiểu 64×64 px.',
        ];
    }
}

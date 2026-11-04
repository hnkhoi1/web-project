<?php

namespace App\Http\Requests;

use App\Models\Note;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $note = $this->route('note');

        return ! $note instanceof Note || ($this->user()?->can('update', $note) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:120'],
            'content' => ['nullable', 'string', 'max:5000'],
            'color' => ['nullable', Rule::in(Note::COLORS)],
            'pinned' => ['sometimes', 'boolean'],
            'source_type' => ['nullable', Rule::in(['article', 'chat'])],
            'source_ref' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.max' => 'Tiêu đề tối đa 120 ký tự.',
            'content.max' => 'Nội dung tối đa 5000 ký tự.',
            'color.in' => 'Màu ghi chú không hợp lệ.',
            'source_type.in' => 'Nguồn ghi chú không hợp lệ.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function ($validator): void {
            if (blank($this->input('title')) && blank($this->input('content'))) {
                $validator->errors()->add('title', 'Hãy nhập tiêu đề hoặc nội dung.');
            }
        }];
    }

    /** The title, or the start of the content when no title was given. */
    public function resolvedTitle(): string
    {
        $title = trim((string) $this->input('title'));

        return $title !== '' ? $title : mb_substr(trim((string) $this->input('content')), 0, 40);
    }
}

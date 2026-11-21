<?php

namespace App\Http\Requests\Admin;

use App\Models\Article;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * An article with all of its clauses, as edited on the admin law form.
 * The article number is chosen on create and fixed afterwards (other tables point at it).
 */
class LawArticleRequest extends FormRequest
{
    public const MAX_CLAUSES = 20;

    private const TEXT = 5000;

    /** Clause range pairs checked for min <= max: field prefix => label. */
    private const RANGES = [
        'ti_le_ton_thuong' => 'tỉ lệ tổn thương',
        'gia_tri_tai_san' => 'giá trị tài sản',
        'so_nguoi_chet' => 'số người chết',
    ];

    public function authorize(): bool
    {
        return true; // The route is behind the admin middleware.
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $creating = ! $this->route('article') instanceof Article;

        return [
            'id' => $creating
                ? ['required', 'integer', 'min:1', 'max:999', Rule::unique('dieu_luat', 'id')]
                : ['prohibited'],
            'chuong' => ['required', 'string', 'max:255'],
            'tieu_de' => ['required', 'string', 'max:255'],
            'tom_tat' => ['required', 'string', 'max:'.self::TEXT],
            'tu_khoa' => ['required', 'string', 'max:'.self::TEXT],
            'khung_hinh_phat' => ['required', 'string', 'max:'.self::TEXT],
            'can_cu_boi_thuong' => ['nullable', 'string', 'max:'.self::TEXT],
            'ghi_chu_quyen_loi' => ['nullable', 'string', 'max:'.self::TEXT],

            'khoan' => ['required', 'array', 'min:1', 'max:'.self::MAX_CLAUSES],
            'khoan.*' => ['array'],
            'khoan.*.so_khoan' => ['required', 'integer', 'min:1', 'max:99', 'distinct'],
            'khoan.*.noi_dung' => ['required', 'string', 'max:'.self::TEXT],
            'khoan.*.dieu_kien' => ['nullable', 'string', 'max:'.self::TEXT],
            'khoan.*.quyen_loi' => ['nullable', 'string', 'max:'.self::TEXT],
            'khoan.*.tu_khoa' => ['nullable', 'string', 'max:'.self::TEXT],
            'khoan.*.cau_hoi_lam_ro' => ['nullable', 'string', 'max:'.self::TEXT],
            'khoan.*.ti_le_ton_thuong_min' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'khoan.*.ti_le_ton_thuong_max' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'khoan.*.gia_tri_tai_san_min' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'khoan.*.gia_tri_tai_san_max' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'khoan.*.so_nguoi_chet_min' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'khoan.*.so_nguoi_chet_max' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id.unique' => 'Điều :input đã có. Hãy sửa điều đó thay vì thêm mới.',
            'id.prohibited' => 'Không được đổi số điều của điều luật đã có.',
            'khoan.required' => 'Điều luật cần ít nhất một khoản.',
            'khoan.min' => 'Điều luật cần ít nhất một khoản.',
            'khoan.max' => 'Mỗi điều tối đa '.self::MAX_CLAUSES.' khoản.',
            'khoan.*.so_khoan.distinct' => 'Số khoản bị trùng.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'id' => 'số điều',
            'chuong' => 'chương',
            'tieu_de' => 'tên tội danh',
            'tom_tat' => 'tóm tắt',
            'tu_khoa' => 'từ khóa',
            'khung_hinh_phat' => 'khung hình phạt',
            'can_cu_boi_thuong' => 'căn cứ bồi thường',
            'ghi_chu_quyen_loi' => 'ghi chú quyền lợi',
            'khoan.*.so_khoan' => 'số khoản',
            'khoan.*.noi_dung' => 'nội dung khoản',
            'khoan.*.dieu_kien' => 'điều kiện',
            'khoan.*.quyen_loi' => 'quyền lợi',
            'khoan.*.tu_khoa' => 'từ khóa khoản',
            'khoan.*.cau_hoi_lam_ro' => 'câu hỏi làm rõ',
            'khoan.*.ti_le_ton_thuong_min' => 'tỉ lệ tổn thương từ',
            'khoan.*.ti_le_ton_thuong_max' => 'tỉ lệ tổn thương đến',
            'khoan.*.gia_tri_tai_san_min' => 'giá trị tài sản từ',
            'khoan.*.gia_tri_tai_san_max' => 'giá trị tài sản đến',
            'khoan.*.so_nguoi_chet_min' => 'số người chết từ',
            'khoan.*.so_nguoi_chet_max' => 'số người chết đến',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            foreach ((array) $this->input('khoan', []) as $i => $clause) {
                foreach (self::RANGES as $field => $label) {
                    $min = $clause[$field.'_min'] ?? null;
                    $max = $clause[$field.'_max'] ?? null;
                    if (is_numeric($min) && is_numeric($max) && (float) $min > (float) $max) {
                        $validator->errors()->add("khoan.$i.{$field}_max", "Mức \"đến\" của $label phải lớn hơn hoặc bằng mức \"từ\".");
                    }
                }
            }
        }];
    }

    /** Trim text and turn blank optional values into null before validating. */
    protected function prepareForValidation(): void
    {
        $clean = fn (mixed $v): mixed => is_string($v) ? (trim($v) === '' ? null : trim($v)) : $v;

        $data = array_map($clean, $this->except('khoan'));
        $clauses = $this->input('khoan');
        if (is_array($clauses)) {
            $data['khoan'] = array_values(array_map(
                fn (mixed $c): mixed => is_array($c) ? array_map($clean, $c) : $c,
                $clauses,
            ));
        }

        $this->replace($data);
    }
}

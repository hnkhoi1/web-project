<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function valid(array $override = []): array
    {
        return array_merge([
            'ho_ten' => 'Nguyễn Văn A',
            'sdt' => '0901 234 567',
            'email' => 'A@Example.com',
            'noi_dung' => 'Tôi muốn hỏi về điều 134.',
        ], $override);
    }

    public function test_pages_render_for_guests(): void
    {
        $this->get('/gioi-thieu')->assertOk();
        $this->get('/lien-he')->assertOk();
    }

    public function test_guest_can_submit(): void
    {
        $this->post('/lien-he', $this->valid())->assertRedirect(route('contact.create'))->assertSessionHasNoErrors();

        $row = Contact::firstOrFail();
        $this->assertNull($row->user_id);
        $this->assertSame('moi', $row->trang_thai);
        $this->assertSame('a@example.com', $row->email);
        $this->assertSame('Nguyễn Văn A', $row->ho_ten);
    }

    public function test_logged_in_user_is_recorded(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/lien-he', $this->valid());

        $this->assertSame($user->id, Contact::firstOrFail()->user_id);
    }

    public function test_phone_is_optional(): void
    {
        $this->post('/lien-he', $this->valid(['sdt' => '']))->assertSessionHasNoErrors();
        $this->assertNull(Contact::firstOrFail()->sdt);
    }

    public function test_invalid_input_is_rejected_in_vietnamese(): void
    {
        foreach ([
            ['sdt', '12ab'],
            ['sdt', '1234567'],
            ['ho_ten', 'A'],
            ['ho_ten', str_repeat('a', 61)],
            ['noi_dung', str_repeat('a', 9)],
            ['noi_dung', str_repeat('a', 2001)],
            ['email', 'khong-hop-le'],
            ['email', str_repeat('a', 250).'@b.vn'],
        ] as [$field, $value]) {
            $this->post('/lien-he', $this->valid([$field => $value]))->assertSessionHasErrors($field);
        }

        $this->assertSame(0, Contact::count());
        $this->post('/lien-he', $this->valid(['noi_dung' => 'ngắn']))
            ->assertSessionHasErrors(['noi_dung' => 'Nội dung câu hỏi phải có ít nhất 10 ký tự.']);
    }
}

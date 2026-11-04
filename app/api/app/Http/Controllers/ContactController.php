<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('contact');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ho_ten' => ['required', 'string', 'min:2', 'max:60'],
            'sdt' => ['nullable', 'string', 'regex:/^\+?[0-9 .\-]{8,15}$/'],
            'email' => ['required', 'email', 'max:254'],
            'noi_dung' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'sdt.regex' => 'Số điện thoại không hợp lệ.',
        ], [
            'ho_ten' => 'họ tên',
            'sdt' => 'số điện thoại',
            'noi_dung' => 'nội dung câu hỏi',
        ]);

        Contact::create([
            ...$data,
            'email' => mb_strtolower($data['email']),
            'user_id' => $request->user()?->id,
            'trang_thai' => 'moi',
        ]);

        return to_route('contact.create');
    }
}

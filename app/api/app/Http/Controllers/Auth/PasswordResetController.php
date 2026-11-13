<?php

namespace App\Http\Controllers\Auth;

use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Forgot-password flow: the user asks for an emailed link, then sets a new password with it.
 */
class PasswordResetController extends Controller
{
    use PasswordValidationRules;

    /** Same answer whether or not the email has an account, so the form cannot be used to probe for accounts. */
    private const SENT_MESSAGE = 'Nếu email này có tài khoản, chúng tôi đã gửi liên kết đặt lại mật khẩu. Hãy kiểm tra hộp thư (cả mục thư rác).';

    public function create(Request $request): Response
    {
        return Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'string', 'email', 'max:254']]);

        // Broker errors (unknown email, throttled) are deliberately not shown.
        Password::sendResetLink(['email' => $this->normalize($data['email'])]);

        return back()->with('status', self::SENT_MESSAGE);
    }

    public function edit(Request $request, string $token): Response
    {
        return Inertia::render('auth/reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
            'passwordRules' => PasswordRule::defaults()->toPasswordRulesString(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge(['email' => $this->normalize((string) $request->input('email'))]);

        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => $this->passwordRules(),
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                // A reset is usually a security response: sign the account out everywhere.
                DB::table('sessions')->where('user_id', $user->id)->delete();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Hãy yêu cầu liên kết mới.',
            ]);
        }

        return to_route('login')->with('status', 'Mật khẩu đã được đặt lại. Hãy đăng nhập bằng mật khẩu mới.');
    }

    private function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}

<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AvatarRequest;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Services\AvatarProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã cập nhật hồ sơ.']);

        return to_route('profile.edit');
    }

    /**
     * Replace the user's avatar with the processed upload.
     */
    public function updateAvatar(AvatarRequest $request, AvatarProcessor $avatars): RedirectResponse
    {
        $request->user()->forceFill([
            'avatar' => $avatars->toDataUri($request->file('avatar')),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã cập nhật ảnh đại diện.']);

        return back();
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['avatar' => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã xóa ảnh đại diện.']);

        return back();
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

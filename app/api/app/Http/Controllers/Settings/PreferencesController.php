<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PreferencesRequest;
use App\Models\UserPref;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PreferencesController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/preferences');
    }

    public function update(PreferencesRequest $request): RedirectResponse
    {
        UserPref::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'theme' => $request->validated('theme'),
                'font' => $request->validated('font'),
                'note_color' => $request->validated('noteColor'),
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã lưu tùy chỉnh.']);

        return back();
    }
}

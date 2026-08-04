<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'ai_typo_prompt' => 'required|string|max:10000',
        ]);

        Setting::setValue('ai_typo_prompt', $data['ai_typo_prompt']);

        return redirect()->route('settings.index')->with('success', 'Pengaturan berhasil disimpan.');
    }
}

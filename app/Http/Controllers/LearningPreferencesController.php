<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LearningPreferencesController extends Controller
{
    public function update(Request $request)
    {
        $preferences = $request->validate(['speaking_enabled' => 'sometimes|required|boolean',
            'audio_enabled' => 'sometimes|required|boolean']);
        $profile = $request->user()->profile;
        abort_unless($profile, 422);
        $profile->update(['learning_preferences' => array_merge($profile->learning_preferences ?? [], $preferences)]);

        return back()->with('success', 'Préférences de séance enregistrées.');
    }
}

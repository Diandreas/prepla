<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'endpoint'    => 'required|string',
            'keys.auth'   => 'required|string',
            'keys.p256dh' => 'required|string',
        ]);

        $request->user()->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth']
        );

        return response()->json(['status' => 'subscribed']);
    }

    public function destroy(Request $request)
    {
        $request->validate(['endpoint' => 'required|string']);

        $request->user()->deletePushSubscription($request->endpoint);

        return response()->json(['status' => 'unsubscribed']);
    }
}

<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\CenterMedia;
use App\Models\LanguageCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        $query = CenterMedia::where('center_id', $center->id)->latest();
        if (in_array($request->query('type'), ['image', 'audio'], true)) {
            $query->where('type', $request->query('type'));
        }

        return response()->json([
            'media' => $query->get()->map(fn (CenterMedia $m) => [
                'id' => $m->id,
                'type' => $m->type,
                'url' => $m->url,
                'name' => $m->original_name,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');

        $validated = $request->validate([
            'type' => 'required|in:image,audio',
            'image' => 'required_if:type,image|image|mimes:jpeg,jpg,png,webp|max:5120',
            'audio' => 'required_if:type,audio|mimes:mp3,wav,webm,ogg,m4a,mpga|max:20480',
        ]);

        $file = $request->file($validated['type']);
        $ext = $file->getClientOriginalExtension() ?: $file->guessExtension();
        // Unique name per upload (no deterministic hash → no accidental overwrite).
        $path = "center-media/{$center->id}/" . Str::uuid() . '.' . $ext;

        Storage::disk('public')->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );

        $media = CenterMedia::create([
            'center_id' => $center->id,
            'uploaded_by' => $request->user()->id,
            'type' => $validated['type'],
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json([
            'id' => $media->id,
            'type' => $media->type,
            'url' => $media->url,
            'name' => $media->original_name,
        ]);
    }

    public function destroy(Request $request, CenterMedia $medium)
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');
        abort_unless($medium->center_id === $center->id, 403);

        Storage::disk('public')->delete($medium->path);
        $medium->delete();

        return response()->json(['ok' => true]);
    }
}

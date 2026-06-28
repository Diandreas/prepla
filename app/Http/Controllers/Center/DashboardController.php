<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\LanguageCenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var LanguageCenter $center */
        $center = $request->attributes->get('center');
        $center->loadCount('classrooms');

        $studentCount = $center->seatsUsed();

        return Inertia::render('center/dashboard', [
            'center' => [
                'id' => $center->id,
                'name' => $center->name,
                'seats_limit' => $center->seats_limit,
                'seats_used' => $studentCount,
                'classrooms_count' => $center->classrooms_count,
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class PublicContentController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'services' => Service::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'publications' => Publication::query()->where('verification_status', 'published')->latest('published_at')->limit(6)->get(),
            'profile' => ['name' => 'Dr. Funmilola Olanike Wuraola', 'title' => 'Breast Oncology Surgeon & Academic Clinician', 'location' => 'Ile-Ife, Nigeria', 'orcid' => '0000-0003-3315-990X'],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __invoke(Request $request, Service $service, AvailabilityService $availability): JsonResponse
    {
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d'], 'method' => ['required', 'in:online,in_person']]);

        return response()->json(['data' => $availability->slots($service, CarbonImmutable::createFromFormat('Y-m-d', $data['date'], 'Africa/Lagos')->startOfDay(), $data['method'])]);
    }
}

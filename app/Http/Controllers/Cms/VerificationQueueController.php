<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\ResearchClaim;
use App\Services\ResearchPublishingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationQueueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = $request->validate(['status' => ['nullable', 'in:pending_review,verified,rejected,published,retracted']])['status'] ?? 'pending_review';

        return response()->json(ResearchClaim::where('status', $status)->latest()->paginate(25));
    }

    public function decide(Request $request, ResearchClaim $claim, ResearchPublishingService $publishing): JsonResponse
    {
        $decision = $request->validate(['decision' => ['required', 'in:verified,rejected']])['decision'];

        return response()->json(['data' => $publishing->review($claim, $request->user(), $decision)]);
    }

    public function publish(Request $request, ResearchClaim $claim, ResearchPublishingService $publishing): JsonResponse
    {
        $result = $publishing->publish($claim, $request->user());

        return response()->json(['data' => $result['claim'], 'record_id' => $result['record']->id]);
    }

    public function retract(Request $request, ResearchClaim $claim, ResearchPublishingService $publishing): JsonResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']])['reason'];

        return response()->json(['data' => $publishing->retract($claim, $request->user(), $reason)]);
    }
}

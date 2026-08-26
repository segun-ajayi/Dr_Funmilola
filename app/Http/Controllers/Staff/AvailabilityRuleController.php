<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\AvailabilityRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AvailabilityRuleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => AvailabilityRule::orderBy('weekday')->orderBy('start_time')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $rule = AvailabilityRule::create($this->validated($request));
        $this->audit($request, $rule, 'availability.created');

        return response()->json(['message' => 'Availability rule created.', 'data' => $rule], 201);
    }

    public function update(Request $request, AvailabilityRule $availabilityRule): JsonResponse
    {
        $availabilityRule->update($this->validated($request));
        $this->audit($request, $availabilityRule, 'availability.updated');

        return response()->json(['message' => 'Availability rule updated.', 'data' => $availabilityRule]);
    }

    private function validated(Request $request): array
    {
        return $request->validate(['weekday' => ['required', 'integer', 'between:1,7'], 'start_time' => ['required', 'date_format:H:i'], 'end_time' => ['required', 'date_format:H:i', 'after:start_time'], 'slot_minutes' => ['required', 'integer', Rule::in([15, 20, 30, 45, 60, 90])], 'buffer_minutes' => ['required', 'integer', 'between:0,60'], 'consultation_method' => ['required', 'in:both,online,in_person'], 'is_active' => ['required', 'boolean']]);
    }

    private function audit(Request $request, AvailabilityRule $rule, string $action): void
    {
        AuditLog::create(['actor_id' => $request->user()->id, 'action' => $action, 'subject_type' => AvailabilityRule::class, 'subject_id' => $rule->id, 'metadata' => $rule->only('weekday', 'start_time', 'end_time', 'consultation_method', 'is_active')]);
    }
}

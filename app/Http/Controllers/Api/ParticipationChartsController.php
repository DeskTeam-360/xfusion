<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\ParticipationChartsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParticipationChartsController extends Controller
{
    public function show(Request $request, Company $company): JsonResponse
    {
        $courseGroupId = (int) $request->query('course_group_id', 0);
        if ($courseGroupId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'course_group_id is required (positive integer).',
            ], 422);
        }

        $payload = ParticipationChartsService::forCompanyAndCourseGroup(
            (int) $company->id,
            $courseGroupId,
            ['radio'],
        );

        if (! ($payload['success'] ?? false)) {
            return response()->json($payload, 404);
        }

        return response()->json($payload);
    }
}

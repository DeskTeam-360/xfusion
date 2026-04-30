<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseGroup;
use Illuminate\Http\JsonResponse;

class CourseGroupPublicController extends Controller
{
    public function index(): JsonResponse
    {
        $groups = CourseGroup::query()
            ->withCount('courseGroupDetails')
            ->orderBy('title')
            ->orderBy('sub_title')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $groups->map(fn (CourseGroup $g) => [
                'id' => $g->id,
                'title' => $g->title,
                'sub_title' => $g->sub_title,
                'courses_count' => $g->course_group_details_count,
            ])->values(),
        ]);
    }
}

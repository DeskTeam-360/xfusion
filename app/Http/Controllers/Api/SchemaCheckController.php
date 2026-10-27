<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SchemaChecker;

class SchemaCheckController extends Controller
{
    public function __invoke(SchemaChecker $checker)
    {
        // Table names are internal detail: only answer when an API token protects the route.
        if (blank(config('fusion_api.token'))) {
            return response()->json(['message' => 'Set FUSION_API_TOKEN to use the schema check.'], 403);
        }

        return response()->json($checker->run());
    }
}

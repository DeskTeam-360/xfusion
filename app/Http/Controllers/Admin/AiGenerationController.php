<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AiUsageService;
use Illuminate\Http\Request;

/**
 * Single Laravel-side list of every AI generation across ARP/QBR/ARR/IRR
 * and 1-on-1 — an alternative to the separate WordPress "History" admin
 * pages, useful when wp-admin access is unavailable or just to see
 * everything in one place.
 */
class AiGenerationController extends Controller
{
    public function index(Request $request, AiUsageService $aiUsage)
    {
        $rows = $aiUsage->allRows();

        $companies = $rows->pluck('company_name')->unique()->sort()->values();

        $module = (string) $request->query('module', '');
        if ($module !== '') {
            $rows = $rows->filter(fn ($r) => $r['module'] === $module)->values();
        }

        $company = (string) $request->query('company', '');
        if ($company !== '') {
            $rows = $rows->filter(fn ($r) => $r['company_name'] === $company)->values();
        }

        $stats = $aiUsage->periodSummaries($rows);
        $perCompany = $aiUsage->perCompany($rows);

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));
        $total = $rows->count();
        $paged = $rows->forPage($page, $perPage)->values();
        $lastPage = (int) max(1, ceil($total / $perPage));

        return view('admin.ai-generation.index', [
            'rows' => $paged,
            'page' => $page,
            'lastPage' => $lastPage,
            'total' => $total,
            'module' => $module,
            'company' => $company,
            'companies' => $companies,
            'stats' => $stats,
            'perCompany' => $perCompany,
        ]);
    }
}

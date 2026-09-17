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

        $module = $request->query('module', '');
        $module = is_string($module) ? $module : '';
        if ($module !== '') {
            $rows = $rows->filter(fn ($r) => $r['module'] === $module)->values();
        }

        $company = $request->query('company', '');
        $company = is_string($company) ? $company : '';
        if ($company !== '') {
            $rows = $rows->filter(fn ($r) => $r['company_name'] === $company)->values();
        }

        $dateFrom = $request->query('date_from', '');
        $dateFrom = is_string($dateFrom) ? $dateFrom : '';
        if ($dateFrom !== '') {
            $rows = $rows->filter(fn ($r) => $r['created_at'] && $r['created_at']->toDateString() >= $dateFrom)->values();
        }

        $dateTo = $request->query('date_to', '');
        $dateTo = is_string($dateTo) ? $dateTo : '';
        if ($dateTo !== '') {
            $rows = $rows->filter(fn ($r) => $r['created_at'] && $r['created_at']->toDateString() <= $dateTo)->values();
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
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'stats' => $stats,
            'perCompany' => $perCompany,
            'filterQuery' => http_build_query(['module' => $module, 'company' => $company, 'date_from' => $dateFrom, 'date_to' => $dateTo]),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyPublicController extends Controller
{
    public function index(Request $request)
    {
        $perPage = max(1, min((int) $request->query('per_page', 50), 100));

        $paginator = Company::query()
            ->with('user')
            ->withCount('companyEmployees')
            ->orderBy('title')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->getCollection()->map(fn (Company $c) => $this->transformCompany($c))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Company $company)
    {
        $company->loadCount('companyEmployees');
        $company->load('user');

        return response()->json([
            'success' => true,
            'data' => $this->transformCompany($company),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformCompany(Company $company): array
    {
        $leader = null;
        if ($company->relationLoaded('user') && $company->user) {
            $u = $company->user;
            $leader = [
                'id' => $u->ID,
                'nicename' => $u->user_nicename,
                'display_name' => $u->display_name ?: $u->user_nicename,
            ];
        }

        return [
            'id' => $company->id,
            'title' => $company->title,
            'company_url' => $company->company_url,
            'logo_url' => $this->publicStorageUrl($company->logo_url),
            'qrcode_url' => $this->publicStorageUrl($company->qrcode_url),
            'employees_count' => (int) ($company->company_employees_count ?? $company->companyEmployees()->count()),
            'leader' => $leader,
        ];
    }

    private function publicStorageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return url(Storage::url($path));
    }
}

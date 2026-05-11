<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CompanyAdmin;
use Illuminate\Support\Facades\Auth;

class CompanyPortalController extends Controller
{
    private function portalCompanyKey(): string
    {
        $cid = CompanyAdmin::portalCompanyMetaId(Auth::user());
        abort_if($cid === null || $cid === '', 403);

        return (string) $cid;
    }

    public function dashboard()
    {
        $companyId = $this->portalCompanyKey();

        return view('admin.company-portal.dashboard', [
            'companyId' => $companyId,
        ]);
    }

    public function users()
    {
        $companyId = $this->portalCompanyKey();

        return view('admin.company-portal.users', [
            'companyId' => $companyId,
        ]);
    }

    /** Form tambah user WordPress baru yang otomatis terhubung ke company portal. */
    public function createUser()
    {
        $companyId = $this->portalCompanyKey();

        return view('admin.company-portal.user-create', [
            'companyId' => $companyId,
        ]);
    }
}

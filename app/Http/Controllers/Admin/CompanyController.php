<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;
use App\Support\CompanyAdmin;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view(
            'admin.company.index'
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.company.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

        $user = Auth::user();
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }
        if ($role == "administrator" || $role == "editor") {
            if ($role == "editor") {
                $user = Auth::user();
                $companies = $user->meta->where('meta_key', '=', 'company');
                foreach ($companies as $r) {
                    if ($r['meta_value'] != $id) {
                        return redirect(route('company.show', $r['meta_value']));
                    }
                }
            }
            return view('admin.company.show', compact('id'));
        } else {
            return redirect('dashboard');
        }


    }

    /**
     * Company-scoped export / participation dashboard (Livewire).
     */
    public function dashboard(string $id)
    {
        $user = Auth::user();

        if (CompanyAdmin::isCompanyAdminPortalUser($user)) {
            $cid = CompanyAdmin::portalCompanyMetaId($user);
            if ($cid === null || (string) $cid !== (string) $id) {
                abort(403);
            }

            return view('admin.company.dashboard', compact('id'));
        }

        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }
        if ($role == 'administrator' || $role == 'editor') {
            if ($role == 'editor') {
                $companies = $user->meta->where('meta_key', '=', 'company');
                foreach ($companies as $r) {
                    if ($r['meta_value'] != $id) {
                        return redirect(route('company.show', $r['meta_value']));
                    }
                }
            }

            return view('admin.company.dashboard', compact('id'));
        }

        return redirect()->route('dashboard');
    }

    public function showDetail(string $id)
    {
        $user = Auth::user();

        if (CompanyAdmin::isCompanyAdminPortalUser($user)) {
            $cid = CompanyAdmin::portalCompanyMetaId($user);
            if ($cid === null || (string) $cid !== (string) $id) {
                abort(403);
            }
        } else {
            $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
            $role = '';
            foreach ($ru as $r) {
                $role = array_key_first(unserialize($r['meta_value']));
            }
            if ($role === 'administrator' || $role === 'editor') {
                if ($role === 'editor') {
                    $companies = $user->meta->where('meta_key', '=', 'company');
                    foreach ($companies as $r) {
                        if ($r['meta_value'] != $id) {
                            return redirect()->route('company.show-detail', $r['meta_value']);
                        }
                    }
                }
            } else {
                return redirect()->route('dashboard');
            }
        }

        $company = Company::find($id);
        if ($company === null) {
            abort(404);
        }

        $companyEmployees = $company->companyEmployees()->get()->pluck('user_id')->toArray();
        $companyEmployeesEntries = \App\Models\WpGfEntry::whereIn('created_by', $companyEmployees)->where('status', 'Active')->get();

        $fusionActivity = [
            [
                'type' => 'arp',
                'label' => 'Annual Readiness Plan™',
                'icon' => 'ti-target-arrow',
                'count' => \App\Models\Arp::where('company_id', $id)->count(),
                'last_at' => \App\Models\Arp::where('company_id', $id)->latest('created_at')->value('created_at'),
            ],
            [
                'type' => 'qbr',
                'label' => 'Quarterly Business Review™',
                'icon' => 'ti-chart-bar',
                'count' => \App\Models\Qbr::where('company_id', $id)->count(),
                'last_at' => \App\Models\Qbr::where('company_id', $id)->latest('created_at')->value('created_at'),
            ],
            [
                'type' => 'arr',
                'label' => 'Annual Readiness Review™',
                'icon' => 'ti-report-analytics',
                'count' => \App\Models\Arr::where('company_id', $id)->count(),
                'last_at' => \App\Models\Arr::where('company_id', $id)->latest('created_at')->value('created_at'),
            ],
            [
                'type' => 'one-on-one',
                'label' => '1-on-1 Alignment Capture™',
                'icon' => 'ti-users',
                'count' => \App\Models\OneOnOneConversation::whereHas('oneOnOne', function ($q) use ($id) {
                    $q->where('company_id', $id);
                })->count(),
                'last_at' => \App\Models\OneOnOneConversation::whereHas('oneOnOne', function ($q) use ($id) {
                    $q->where('company_id', $id);
                })->latest('created_at')->value('created_at'),
            ],
            [
                'type' => 'irr',
                'label' => 'Individual Readiness Review™',
                'icon' => 'ti-user-check',
                'count' => \App\Models\IrrReview::where('company_id', $id)->count(),
                'last_at' => \App\Models\IrrReview::where('company_id', $id)->latest('created_at')->value('created_at'),
            ],
        ];

        return view('admin.company.show-detail', compact('id', 'company', 'companyEmployeesEntries', 'fusionActivity'));
    }

    /**
     * WordPress front-end page + query param for each FUSION component's
     * wizard, so an activity record can link straight to it.
     *
     * @return array{slug: string, param: string}
     */
    private function fusionActivityWpRoute(string $type): array
    {
        return match ($type) {
            'arp' => ['slug' => 'annual-readiness-plan', 'param' => 'arp_id'],
            'qbr' => ['slug' => 'quarterly-business-review', 'param' => 'qbr_id'],
            'arr' => ['slug' => 'annual-readiness-review', 'param' => 'arr_id'],
            'irr' => ['slug' => 'individual-readiness-review', 'param' => 'irr_id'],
            'one-on-one' => ['slug' => '1-on-1-alignment', 'param' => 'conversation_id'],
            default => ['slug' => '', 'param' => ''],
        };
    }

    /** List of a single FUSION component's records for this company, each linking out to its WordPress wizard. */
    public function activityDetail(string $id, string $type)
    {
        $company = Company::find($id);
        if ($company === null) {
            abort(404);
        }

        $wpRoute = $this->fusionActivityWpRoute($type);
        if ($wpRoute['slug'] === '') {
            abort(404);
        }

        $wpBase = \App\Support\WordpressPublicUrl::base() . '/' . $wpRoute['slug'] . '/';

        $userLabel = fn ($user) => $user ? ($user->display_name ?: $user->user_nicename) : '—';

        $records = match ($type) {
            'arp' => \App\Models\Arp::where('company_id', $id)->orderByDesc('created_at')->get()->map(fn ($r) => [
                'title' => 'ARP ' . $r->year,
                'meta' => null,
                'status' => $r->status,
                'created_at' => $r->created_at,
                'wp_url' => $wpBase . '?' . $wpRoute['param'] . '=' . $r->id,
            ]),
            'qbr' => \App\Models\Qbr::where('company_id', $id)->with('companyGroup:id,title')->orderByDesc('created_at')->get()->map(fn ($r) => [
                'title' => 'Q' . $r->quarter . ' ' . $r->year,
                'meta' => $r->companyGroup?->title ?: '—',
                'status' => $r->status,
                'created_at' => $r->created_at,
                'wp_url' => $wpBase . '?' . $wpRoute['param'] . '=' . $r->id,
            ]),
            'arr' => \App\Models\Arr::where('company_id', $id)->orderByDesc('created_at')->get()->map(fn ($r) => [
                'title' => 'ARR ' . $r->year,
                'meta' => null,
                'status' => $r->status,
                'created_at' => $r->created_at,
                'wp_url' => $wpBase . '?' . $wpRoute['param'] . '=' . $r->id,
            ]),
            'irr' => \App\Models\IrrReview::where('company_id', $id)
                ->with(['employee:ID,display_name,user_nicename', 'manager:ID,display_name,user_nicename'])
                ->orderByDesc('created_at')->get()->map(fn ($r) => [
                    'title' => 'IRR ' . $r->year,
                    'meta' => $userLabel($r->employee) . ' — ' . $userLabel($r->manager),
                    'status' => $r->status,
                    'created_at' => $r->created_at,
                    'wp_url' => $wpBase . '?' . $wpRoute['param'] . '=' . $r->id,
                ]),
            'one-on-one' => \App\Models\OneOnOneConversation::whereHas('oneOnOne', function ($q) use ($id) {
                $q->where('company_id', $id);
            })->with(['oneOnOne.leader:ID,display_name,user_nicename', 'oneOnOne.employee:ID,display_name,user_nicename'])
                ->orderByDesc('created_at')->get()->map(fn ($r) => [
                    'title' => 'Meeting #' . $r->id,
                    'meta' => $userLabel($r->oneOnOne?->leader) . ' — ' . $userLabel($r->oneOnOne?->employee),
                    'status' => $r->status,
                    'created_at' => $r->created_at,
                    'wp_url' => $wpBase . '?' . $wpRoute['param'] . '=' . $r->id,
                ]),
            default => collect(),
        };

        $label = collect([
            'arp' => 'Annual Readiness Plan™',
            'qbr' => 'Quarterly Business Review™',
            'arr' => 'Annual Readiness Review™',
            'irr' => 'Individual Readiness Review™',
            'one-on-one' => '1-on-1 Alignment Capture™',
        ])->get($type, $type);

        $metaColumnLabel = collect([
            'qbr' => 'Group',
            'irr' => 'Employee — Manager',
            'one-on-one' => 'Leader — Employee',
        ])->get($type);

        return view('admin.company.activity-detail', compact('id', 'company', 'type', 'label', 'records', 'metaColumnLabel'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return view('admin.company.edit', compact('id'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function addEmployee(string $id)
    {
        $user = Auth::user();
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }
        if ($role == "administrator" || $role == "editor") {
            if ($role == "editor") {
                $user = Auth::user();
                $companies = $user->meta->where('meta_key', '=', 'company');
                foreach ($companies as $r) {
                    if ($r['meta_value'] != $id) {
                        return redirect(route('company.add-employee', $r['meta_value']));
                    }
                }
            }
            return view('admin.company.add-employee', compact('id'));
        } else {
            return redirect('dashboard');
        }

    }

    public function addEmployeeFromUsers(string $id)
    {
        $user = Auth::user();
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }
        if ($role == "administrator" || $role == "editor") {
            if ($role == "editor") {
                $user = Auth::user();
                $companies = $user->meta->where('meta_key', '=', 'company');
                foreach ($companies as $r) {
                    if ($r['meta_value'] != $id) {
                        return redirect(route('company.add-employee-from-users', $r['meta_value']));
                    }
                }
            }
            return view('admin.company.add-employee-from-users', compact('id'));
        } else {
            return redirect('dashboard');
        }
    }

    public function editEmployee(string $id, string $employee)
    {
        $user = Auth::user();

        if (CompanyAdmin::isCompanyAdminPortalUser($user)) {
            $cid = CompanyAdmin::portalCompanyMetaId($user);
            if ($cid !== null && (string) $cid === (string) $id) {
                return view('admin.company.edit-employee', compact('id', 'employee'));
            }
            abort(403);
        }

        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }
        if ($role == "administrator" || $role == "editor") {
            if ($role == "editor") {
                $user = Auth::user();
                $companies = $user->meta->where('meta_key', '=', 'company');
                foreach ($companies as $r) {
                    if ($r['meta_value'] != $id) {
                        return redirect(route('company.add-employee', $r['meta_value']));
                    }
                }
            }
            return view('admin.company.edit-employee', compact('id', 'employee'));
        } else {
            return redirect('dashboard');
        }
    }

    public function progress(string $id)
    {
        $user = Auth::user();
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }
        if ($role == "administrator" || $role == "editor") {
            if ($role == "editor") {
                $user = Auth::user();
                $companies = $user->meta->where('meta_key', '=', 'company');
                foreach ($companies as $r) {
                    if ($r['meta_value'] != $id) {
                        return redirect(route('company.progress', $r['meta_value']));
                    }
                }
            }
            return view('admin.company.progress', compact('id'));
        } else {
            return redirect('dashboard');
        }
    }

    public function schedule(string $id)
    {
        $user = Auth::user();
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }
        if ($role == "administrator" || $role == "editor") {
            if ($role == "editor") {
                $user = Auth::user();
                $companies = $user->meta->where('meta_key', '=', 'company');
                foreach ($companies as $r) {
                    if ($r['meta_value'] != $id) {
                        return redirect(route('company.schedule', $r['meta_value']));
                    }
                }
            }
            return view('admin.company.schedule', compact('id'));
        } else {
            return redirect('dashboard');
        }
    }

    public function scheduleCreate(string $id)
    {
        $user = Auth::user();
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }
        if ($role == "administrator" || $role == "editor") {
            if ($role == "editor") {
                $user = Auth::user();
                $companies = $user->meta->where('meta_key', '=', 'company');
                foreach ($companies as $r) {
                    if ($r['meta_value'] != $id) {
                        return redirect(route('company.schedule-create', $r['meta_value']));
                    }
                }
            }
            return view('admin.company.create-schedule', compact('id'));
        } else {
            return redirect('dashboard');
        }
    }

    public function scheduleUser(string $id, string $user)
    {
        $userID=$user;
        $user = Auth::user();
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }
        if ($role == "administrator" || $role == "editor") {
            if ($role == "editor") {
                $user = Auth::user();
                $companies = $user->meta->where('meta_key', '=', 'company');
                foreach ($companies as $r) {
                    if ($r['meta_value'] != $id) {
                        return redirect(route('company.show', $r['meta_value']));
                    }
                }
            }
            return view('admin.company.schedule-employee', compact('id', 'userID'));
        } else {
            return redirect('dashboard');
        }
    }

    public function resultUser(string $id, string $user)
    {
        $userID=$user;
        $user = Auth::user();
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }
        if ($role == "administrator" || $role == "editor") {
            if ($role == "editor") {
                $user = Auth::user();
                $companies = $user->meta->where('meta_key', '=', 'company');
                foreach ($companies as $r) {
                    if ($r['meta_value'] != $id) {
                        return redirect(route('company.show', $r['meta_value']));
                    }
                }
            }
            return view('admin.company.result-employee', compact('id', 'userID'));
        } else {
            return redirect('dashboard');
        }
    }

    public function scheduleUserAdministrator($user)
    {
        $id = null;
        $userID=$user;
        return view('admin.company.schedule-employee', compact('userID', 'id'));
    }

    public function courseScheduleGenerate()
    {
        return view('admin.schedule.course-schedule-generate');
    }

    public function courseScheduleGenerateCreate()
    {
        return view('admin.schedule.course-schedule-generate-create');
    }

    public function courseScheduleGenerateEdit($id)
    {
        return view('admin.schedule.course-schedule-generate-edit',compact('id'));
    }


}

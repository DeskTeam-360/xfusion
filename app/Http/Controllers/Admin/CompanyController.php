<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;
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

    public function showDetail(string $id)
    {
        $company = Company::find($id);

        $companyEmployees = $company->companyEmployees()->get()->pluck('user_id')->toArray();
        $companyEmployeesEntries = \App\Models\WpGfEntry::whereIn('created_by', $companyEmployees)->where('status', 'Active')->get();
        // dd($companyEmployeesEntries);
        // dd($companyEmployees);
        // dd($companyEmployees);
        return view('admin.company.show-detail', compact('id', 'company', 'companyEmployeesEntries'));
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

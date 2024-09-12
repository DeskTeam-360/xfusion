<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    public function exportToCSV()
    {
//        dd(Company::whereId(CompanyEmployee::whereUserId(32)->get()[0]->company_id)->get('title')[0]->title);
        $fileName = 'users.csv';

        $auth_roles = Auth::user()->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $auth_role = '';

        foreach ($auth_roles as $ar) {
            $auth_role = array_key_first(unserialize($ar['meta_value']));
        }

        if ($auth_role == "administrator") {
            $users = User::all();
        } else {
            $companies = Auth::user()->meta->where('meta_key', '=', 'company');
            foreach ($companies as $r) {
                $c = \App\Models\Company::find($r['meta_value']);
                if ($c != null) {
                    $companyId = $c->id;
                    $company = $c->title;
                } else {
                    $company = 'Company has been delete';
                }
            }

            $auth_company_employees = CompanyEmployee::whereCompanyId($companyId)->pluck('user_id');
            $users = User::whereIn('id', $auth_company_employees)->get();
        }

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['ID', 'Name', 'Email', 'Company', 'Role'];

        $callback = function() use($users, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($users as $user) {
                $row['ID']  = $user->ID;
                $row['Name']    = $user->user_nicename;
                $row['Email']   = $user->user_email;
                try {
                    $row['Company']  = Company::whereId(CompanyEmployee::whereUserId($user->ID)->get()[0]->company_id)->get('title')[0]->title;
                } catch (\Exception) {
                    $row['Company']  = "-";
                }

                try {
                    $roles = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
                    $role = '';

                    foreach ($roles as $r) {
                        $role = array_key_first(unserialize($r['meta_value']));
                    }
                    $row['Role']  = $role;
                } catch (\Exception) {
                    $row['Role']  = '-';
                }

                fputcsv($file, [$row['ID'], $row['Name'], $row['Email'], $row['Company'], $row['Role']]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadTemplate()
    {
        $filePath = 'csv_templates/csv_import_example.csv'; // Path to the CSV file
        $fileName = 'csv_import_example.csv'; // Desired filename for download

        return response()->download(public_path('/assets/'. $fileName));
    }
}

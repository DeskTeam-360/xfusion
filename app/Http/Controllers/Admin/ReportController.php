<?php

namespace App\Http\Controllers\Admin;

use App\Models\CourseGroup;
use App\Models\CourseList;
use App\Models\ScheduleExecution;
use App\Models\Season;
use App\Models\User;
use App\Models\WpGfEntry;
use App\Models\WpGfForm;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\WpGfFormMeta;
use App\Models\WpGfEntryMeta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }

        $data = Season::all();

        if ($role == "editor") {
            $user = Auth::user();
            $companies = $user->meta->where('meta_key', '=', 'company');

            foreach ($companies as $r) {
                $companyId = $r['meta_value'];
            }
//            dd($companyId);
            return view(
                'admin.report.index', compact('data', 'role', 'companyId')
            );
        }

        return view(
            'admin.report.index', compact('data', 'role')
        );
    }

    public function seasonCourseEmployee($id)
    {
        $user = Auth::user();
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }

        $data = ScheduleExecution::where('season_id', $id)
                            ->distinct()
                            ->pluck('user_id');

        return view(
            'admin.report.season-course-employee', compact('data', 'id', 'role')
        );
    }

    public function seasonCourseCompany($companyId, $id)
    {
        $user = Auth::user();
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }

        $data = ScheduleExecution::where('season_id', $id)
            ->where('company_id', $companyId)
            ->distinct()
            ->pluck('user_id');

        return view(
            'admin.report.season-course-employee', compact('data', 'id', 'companyId', 'role')
        );
    }

    public function seasonCourseIndex($id, $d)
    {
        $user = Auth::user();
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }

        $data_form = ScheduleExecution::where('user_id', $d)->get();
//        dd($data_form);
        $season_id = $id;
        $user_id = $d;

        $form_id = CourseList::where('url', 'https://teamsetup-2.deskteam360.com/revitalize/lms-page-'. $id .'/')
                ->pluck('wp_gf_form_id')[0];
        $entry_id = WpGfEntry::select('id', 'created_by', 'date_created')
                    ->where('form_id', $form_id)
                    ->where('created_by', $user_id)
                    ->whereNotNull('created_by')
                    ->whereIn(DB::raw('(created_by, date_created)'), function($query) use ($form_id) {
                        $query->select(DB::raw('created_by, MAX(date_created)'))
                              ->from('wp_gf_entry')
                              ->where('form_id', $form_id)
                              ->whereNotNull('created_by')
                              ->groupBy('created_by');
                    })
                    ->pluck('id')[0];

//        dd($entry_id);
        return view(
            'admin.report.season-course-index', compact('data_form', 'season_id', 'user_id', 'role')
        );
    }

    public function courseDetail($seasonId, $userId, $formId, $entryId)
    {
        $user = Auth::user();
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }

        $season_id = $seasonId;
        $user_id = $userId;

        $data = WpGfFormMeta::where('form_id', $formId)->first();
//        dd($data);
        $data_entry = WpGfEntryMeta::where('form_id', $formId)->where('entry_id', $entryId)->get();
//        dd($data, $data_entry);
//        foreach ($data_entry as $entry) {
//
//            dd($entry->wpGfEntry);
//            if ($entry->wpGfEntry['date_created'] == $dateCreated) {
//                dd($entry);
//            }
//
//
//        }

        $lms = $data->wpGfForm->title;
        $count_fields = 0;
        $count_entry = 0;
        $array_entry = [];

        // dd($data_entry[0]->getAttributes()['meta_value']);

        foreach($data->getFields()->fields as $field){
//             dd($field);
            $count_fields += 1;
            $array_entry[$field->id] = null;
        }

        foreach ($data_entry as $entry){
//            dd($entry['meta_value']);
            $array_entry[$entry->meta_key] = $entry['meta_value'];
        }

//        dd($array_entry);

        $data_fields = $data->getFields()->fields;

        // dd($count_fields);
        return view('admin.report.season-employee-detail', compact('season_id', 'user_id','data_fields', 'lms', 'count_fields', 'array_entry','entryId', 'role'));
    }
}

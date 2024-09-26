<?php

namespace App\Http\Controllers\Admin;

use App\Models\CourseGroup;
use App\Models\CourseGroupBackup;
use App\Models\CourseGroupDetail;
use App\Models\CourseList;
use App\Models\Level;
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
    private function auth()
    {
        $user = Auth::user();
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }

        return $role;
    }
    public function index()
    {
        $role = $this->auth();

        $data = CourseGroup::where('title', 'Revitalize')->get();

        $data_level = CourseGroup::where('title', 'Sustain')->get();

        if ($role == "editor") {
            $user = Auth::user();
            $companies = $user->meta->where('meta_key', '=', 'company');

            foreach ($companies as $r) {
                $companyId = $r['meta_value'];
            }
            return view(
                'admin.report.index', compact('data', 'data_level', 'role', 'companyId')
            );
        }

        return view(
            'admin.report.index', compact('data', 'data_level', 'role')
        );
    }

    public function seasonCourseEmployee($id)
    {
        $role = $this->auth();

        $data = ScheduleExecution::where('season_id', $id)
                            ->distinct()
                            ->pluck('user_id');

        return view(
            'admin.report.season-course-employee', compact('data', 'id', 'role')
        );
    }

    public function seasonCourseCompany($companyId, $id)
    {
        $role = $this->auth();

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
        $role = $this->auth();

        $data_form = CourseGroupDetail::where('course_group_id', $id)->get();
        $season_id = $id;
        $user_id = $d;

        return view(
            'admin.report.season-course-index', compact('data_form', 'season_id', 'user_id', 'role')
        );
    }

    public function courseDetail($seasonId, $userId, $formId, $entryId)
    {
        $role = $this->auth();

        $season_id = $seasonId;
        $user_id = $userId;

        $data = WpGfFormMeta::where('form_id', $formId)->first();
        $data_entry = WpGfEntryMeta::where('form_id', $formId)->where('entry_id', $entryId)->get();

        $lms = $data->wpGfForm->title;
        $count_fields = 0;
        $array_entry = [];

        foreach($data->getFields()->fields as $field){
            $count_fields += 1;
            $array_entry[$field->id] = null;
        }

        foreach ($data_entry as $entry){
            $array_entry[$entry->meta_key] = $entry['meta_value'];
        }

        $data_fields = $data->getFields()->fields;

        return view('admin.report.season-employee-detail', compact('season_id', 'user_id','data_fields', 'lms', 'count_fields', 'array_entry','entryId', 'role'));
    }

    public function levelCourseEmployee($id)
    {
        $role = $this->auth();

        $data = ScheduleExecution::where('level_id', $id)
            ->distinct()
            ->pluck('user_id');

        return view(
            'admin.report.level-course-employee', compact('data', 'id', 'role')
        );
    }

    public function levelCourseCompany($companyId, $id)
    {
        $role = $this->auth();

        $data = ScheduleExecution::where('level_id', $id)
            ->where('company_id', $companyId)
            ->distinct()
            ->pluck('user_id');

        return view(
            'admin.report.level-course-employee', compact('data', 'id', 'companyId', 'role')
        );
    }

    public function levelCourseIndex($id, $dl)
    {
        $role = $this->auth();

        $data_form = ScheduleExecution::where('user_id', $dl)->get();
        $level_id = $id;
        $user_id = $dl;

        return view(
            'admin.report.level-course-index', compact('data_form', 'level_id', 'user_id', 'role')
        );
    }

    public function levelDetail($levelId, $userId, $formId, $entryId)
    {
        $role = $this->auth();

        $level_id = $levelId;
        $user_id = $userId;

        $data = WpGfFormMeta::where('form_id', $formId)->first();
        $data_entry = WpGfEntryMeta::where('form_id', $formId)->where('entry_id', $entryId)->get();

        $lms = $data->wpGfForm->title;
        $count_fields = 0;
        $array_entry = [];

        foreach($data->getFields()->fields as $field){
            $count_fields += 1;
            $array_entry[$field->id] = null;
        }

        foreach ($data_entry as $entry){
            $array_entry[$entry->meta_key] = $entry['meta_value'];
        }

        $data_fields = $data->getFields()->fields;

        return view('admin.report.level-employee-detail', compact('level_id', 'user_id','data_fields', 'lms', 'count_fields', 'array_entry','entryId', 'role'));
    }

}


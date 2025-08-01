<?php

use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\CourseGroupController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\LimitLinkController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UserController;
use App\Models\CourseGroup;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard',);
},);

Route::get('/home', function () {
    return redirect()->route('dashboard',);
},);

//Route::get('/settings', function () {
//
//    $form_ids = [35,52,51,53,54,55,66,58,65,96,61,97,83,98,77,81,80,99,72,74,87,62,100,101,102,63,88,103,36,67,68,84,86,73,145,144,70,71,69,146,78,147,85,82,56,76,59,64,60,148,79,104,105,106,107,108,109,110,111,112,113,114,115,116,117,118,119,120,121,123,125,290,126,127,128,130,131,132,133,134,135,136,137,138,139,140,142,143,150,151,152,153,154,155,230,231,232,233,234,235,149,236,237,2,3,24,289,7,8,29,9,276,30,4,14,13,15,278,16,279,280,17,281,325,282,323,324,75,122,306,293,292,293,294,295,296,297,300,301,299,298,303,302,373,311,312,313,314,315,316,317,318,322,319,320,371,371,372,238,239,240,241,242,243,244,245,167,246,247,248,249,250,251,252,253,156,254,256,257,185,259,158,260,261,262,263,266,264,265,291,268,270,271,269,272,273,274,327,328,330,331,332,334,335,336,337,338,339,340,341,342,343,344,345,346,347,348,349,350,351,352,353,354,355,356,357,358,359,360,361,191,363,364,365,366,367,368,369,199,200,217,202,201,209,213,206,207,208,210,212,226,203,211,214,219,218,215,382,216,384,383,221,205,222,229,225,223,227,224,228,204,362];
//
//    $metaJson = json_encode([
//        "feedName"                                => "Next Course",
//        "requestURL"                              => "https://admin.demo.xperiencefusion.com/api/next-course",
//        "requestMethod"                           => "POST",
//        "requestFormat"                           => "json",
//        "requestHeaders"                          => [
//            [
//                "key"          => "",
//                "custom_key"   => "",
//                "value"        => "",
//                "custom_value" => "",
//            ],
//        ],
//        "requestBodyType"                         => "select_fields",
//        "fieldValues"                             => [
//            [
//                "key"          => "gf_custom",
//                "custom_key"   => "entry_id",
//                "value"        => "id",
//                "custom_value" => "",
//            ],
//        ],
//        "feed_condition_conditional_logic_object" => [],
//        "feed_condition_conditional_logic"        => "0",
//        "feedCondition"                           => "",
//    ]);
//
//    foreach ($form_ids as $formId) {
//        $exists = DB::table('wp_gf_addon_feed')->where('form_id', $formId)->exists();
//
//        if (!$exists) {
//            DB::table('wp_gf_addon_feed')->insert([
//                'form_id'    => $formId,
//                'is_active'  => 1,
//                'feed_order' => 0,
//                'meta'       => $metaJson,
//                'addon_slug' => 'gravityformswebhooks',
//                'event_type' => null,
//            ]);
//        }
//    }
//
//    return [
//        'code'   => 200,
//        'status' => 'success',
//    ];
//
//},);

Auth::routes();

Route::middleware(['auth',],)->group(function () {
    Route::get('/dashboard', function () {

        $user = Auth::user();
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_',) . 'capabilities',);
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value'],),);
        }
        if ($role == 'administrator') {
            return view('admin.index',);
        } else if ($role == 'editor') {
            return view('admin.dashboard-company',);
        } else {
            return view('admin.dashboard-contributor',);
        }
    },)->name('dashboard',);

    Route::get('user/connect-keap/{user}', function ($user,) {
        return view('admin.user.keap-connect', compact('user',),);
    },)->name('user.connect-keap',);

    Route::get('user/course/{user}', function ($user,) {
        return view('admin.user.course', compact('user',),);
    },)->name('user.course',);

    Route::get('user/course/{user}/details', function ($user,) {
        return view('admin.user.course', compact('user',),);
    },)->name('user.course',);

    Route::get('user/access/{user}/', function ($user,) {
        return view('admin.user.show-access', compact('user',),);
    },)->name('user.show-access',);

    Route::get('user-roles/', function () {
        return view('admin.user.roles',);
    },)->name('user.roles',);

    Route::resource('campaign', CampaignController::class,)->only('index', 'create', 'edit',);
    Route::resource('company', CompanyController::class,)->only('index', 'create', 'edit',);
    Route::resource('user', UserController::class,)->only('index', 'create', 'edit', 'show',);
    Route::resource('report', ReportController::class,)->only('index', 'create', 'edit',);
    Route::resource('tag', TagController::class,)->only('index', 'create', 'show',);

    Route::resource('course-title', LimitLinkController::class,)->only('index', 'create', 'edit',);
    Route::resource('course-group', CourseGroupController::class,)->only('index', 'create', 'edit', 'show',);

    Route::get('schedule', function () {
        return view('admin.schedule.index',);
    },)->name('schedule-all',);
    Route::get('revitalize', function () {
        return view('admin.revitalize.index',);
    },)->name('revitalize-all',);

    Route::get('/schedule/user/detail/{user}', [
        CompanyController::class,
        'scheduleUserAdministrator',
    ],)->name('schedule-user-administrator',);

    Route::get('/course/schedule/generate/', [
        CompanyController::class,
        'courseScheduleGenerate',
    ],)->name('course-schedule-generate',);
    Route::get('/course/schedule/generate/create', [
        CompanyController::class,
        'courseScheduleGenerateCreate',
    ],)->name('course-schedule-generate-create',);
    Route::get('/course/schedule/generate/edit/{id}', [
        CompanyController::class,
        'courseScheduleGenerateEdit',
    ],)->name('course-schedule-generate-edit',);

    Route::get('/contacts/{contactId}', [
        ContactController::class,
        'see_contacts',
    ],)->name('contacts_show',);
    Route::get('/apply-tags/{contactId}/{tagId}', [
        ContactController::class,
        'applyTags',
    ],)->name('apply_tag',);
    Route::get('/tag-list/', [
        ContactController::class,
        'tag_list',
    ],)->name('tag_list',);
    Route::get('/campaign/create/group', [
        CampaignController::class,
        'create_company',
    ],)->name('create_company',);
    Route::get('/user/{userId}/create/campaign', [
        CampaignController::class,
        'create_independent_user',
    ],)->name('create_independent_user',);
    Route::get('/user/{userId}/tag-list', [
        CampaignController::class,
        'listTag',
    ],)->name('user.tag-list',);
//    });

//    Route::middleware([
//        'auth', 'checkRole:editor,administrator',
//    ])->group(function () {
    Route::resource('report', ReportController::class,)->only('index', 'create', 'edit',);

    Route::get('/report/course-group/{id}', function ($id,) {

        $courseGroup = CourseGroup::find($id,);

        $formIds = [];
        foreach ($courseGroup->courseGroupDetails as $cgd) {
            $formIds[] = $cgd->courseList->wp_gf_form_id;
        }

        $user = Auth::user();
        $userId = $user->ID;
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_',) . 'capabilities',);
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value'],),);
        }

        $data = User::join('gf_entry', 'users.ID', '=', 'gf_entry.created_by',)->whereIn('gf_entry.form_id', $formIds,)->pluck('users.ID',);
        $data = array_unique($data->toArray(),);

        return view('admin.report.level-course-employee', compact('data', 'id', 'role', 'userId',),);
    },)->name('report.course-group',);

    Route::get('/report/course-group/{id}/user/{user}', function ($id, $user,) {
        $courseGroup = CourseGroup::find($id,);
        return view('admin.report.report-detail', compact('user', 'id', 'courseGroup',),);
    },)->name('report.course-group.user',);


    Route::get('/report/season-{seasonId}/user-{userId}/course', [
        ReportController::class,
        'seasonCourseIndex',
    ],)->name('season-course-index',);
    Route::get('/report/season-{seasonId}/user-{userId}/form-{formId}/entry-{entryId}/detail', [
        ReportController::class,
        'courseDetail',
    ],)->name('course-detail',);

//        Route::get('/report/season-{seasonId}', [ReportController::class, 'seasonCourseEmployee'])->name('season-course-employee');
//        Route::get('/report/company-{companyId}/season-{seasonId}', [ReportController::class, 'seasonCourseCompany'])->name('season-course-company');
//
//        Route::get('/report/company-{companyId}/level-{levelId}', [ReportController::class, 'levelCourseCompany'])->name('level-course-company');
//        Route::get('/report/level-{levelId}', [ReportController::class, 'levelCourseEmployee'])->name('level-course-employee');
//        Route::get('/report/level-{levelId}/user-{userId}/course', [ReportController::class, 'levelCourseIndex'])->name('level-course-index');
//        Route::get('/report/level-{levelId}/user-{userId}/form-{formId}/entry-{entryId}/detail', [ReportController::class, 'levelDetail'])->name('level-detail');


//        Route::get('course-group', function (){
//            return view();
//        });

    Route::get('/export-user/', [
        ExportController::class,
        'exportToCSV',
    ],)->name('export-user',);
    Route::get('/export-user-company/{id}', [
        ExportController::class,
        'exportToCSVCompany',
    ],)->name('export-user-company',);
    Route::get('/template-download', [
        ExportController::class,
        'downloadTemplate',
    ],)->name('template-download',);
    Route::get('/import-user', [
        ImportController::class,
        'importIndex',
    ],)->name('to-import-user',);
    Route::post('/import-user-action', [
        ImportController::class,
        'importCSV',
    ],)->name('import-user',);

//    });
    Route::get('company/{id}', [
        CompanyController::class,
        'show',
    ],)->name('company.show',);
    Route::get('/company/{id}/add-employee', [
        CompanyController::class,
        'addEmployee',
    ],)->name('company.add-employee',);
    Route::get('/company/{id}/edit-employee/{employee}', [
        CompanyController::class,
        'editEmployee',
    ],)->name('company.edit-employee',);

    Route::get('/company/{id}/progress', [
        CompanyController::class,
        'progress',
    ],)->name('company.progress',);
    Route::get('/company/{id}/schedule', [
        CompanyController::class,
        'schedule',
    ],)->name('company.schedule',);
    Route::get('/company/{id}/schedule/create', [
        CompanyController::class,
        'scheduleCreate',
    ],)->name('company.schedule-create',);

    Route::get('/company/{id}/schedule/user/{user}', [
        CompanyController::class,
        'scheduleUser',
    ],)->name('company.schedule-user',);

    Route::get('/company/{id}/result/user/{user}', [
        CompanyController::class,
        'resultUser',
    ],)->name('company.result-user',);

},);


Route::get('/home', [
    App\Http\Controllers\HomeController::class,
    'index',
],)->name('home',);

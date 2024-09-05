<?php

use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\LimitLinkController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use App\Models\Tag;
use App\Models\User;
use App\Models\WpUserMeta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use KeapGeek\Keap\Facades\Keap;

Route::get('/', function () {
//    $k=Keap::contact()->list([
//        'email' =>'mokhamadasif@gmail.com'
//    ])[0];
//            dd($k);
//    return redirect('/keap/auth/');
    return redirect(route('dashboard'));
});

Auth::routes();

Route::middleware([
    'auth'
])->group(function () {
    Route::get('/dashboard', function () {
        $tag = [];
        $users = User::whereHas('meta',function ($q){
            $q->where('meta_key', '=', 'keap_contact_id');
        })->get();
        foreach ($users as $user){
            $wpUserMeta = WpUserMeta::where('user_id','=',$user->ID)->where('meta_key','=','keap_tags')->first();
            $keapId = WpUserMeta::where('user_id','=',$user->ID)->where('meta_key','=','keap_contact_id')->first()->meta_value;
            $tagKeaps = Keap::contact()->tags($keapId);
            foreach ($tagKeaps as $tk){
                $tag[]=$tk['tag']['id'];
            }
            $tag = implode(';',$tag);
            if ($wpUserMeta!=null){
                WpUserMeta::find($wpUserMeta->umeta_id)->update(['meta_value'=>$tag]);
            }else{
                WpUserMeta::create([
                    'user_id'=>$user->ID,
                    'meta_key'=>'keap_tags',
                    'meta_value'=>$tag
                ]);
            }
        }

        $tags = Keap::tag()->list([
            'category' => 44
        ]);

        foreach ($tags as $tag){
            $t = Tag::find($tag['id']);
            $tag['category'] = $tag['category']['id'];
            if ($t!=null){
                $t->update($tag);
            }else{
                Tag::create($tag);
            }
        }

        $user = Auth::user();
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }
        if ($role == 'administrator') {
            return view('admin.index');
        } elseif ($role == 'editor') {
            return view('admin.dashboard-company');
        } else {
            return view('admin.dashboard-contributor');
        }


    })->name('dashboard');

    Route::middleware([
        'auth', 'checkRole:administrator'
    ])->group(function () {

        Route::get('user/connect-keap/{user}', function ($user) {
            return view('admin.user.keap-connect', compact('user'));
        })->name('user.connect-keap');

        Route::resource('campaign', CampaignController::class)->only('index', 'create', 'edit');
        Route::resource('company', CompanyController::class)->only('index', 'create', 'edit');
        Route::resource('user', UserController::class)->only('index', 'create', 'edit', 'show');
        Route::resource('report', ReportController::class)->only('index', 'create', 'edit');

        Route::resource('course-title', LimitLinkController::class)->only('index', 'create', 'edit');

        Route::get('schedule', function () {
            return view('admin.schedule.index');
        })->name('schedule-all');
        Route::get('revitalize', function () {
            return view('admin.revitalize.index');
        })->name('revitalize-all');

        Route::get('/schedule/user/detail/{user}', [CompanyController::class, 'scheduleUserAdministrator'])->name('schedule-user-administrator');

        Route::get('/course/schedule/generate/', [CompanyController::class, 'courseScheduleGenerate'])->name('course-schedule-generate');
        Route::get('/course/schedule/generate/create', [CompanyController::class, 'courseScheduleGenerateCreate'])->name('course-schedule-generate-create');
        Route::get('/course/schedule/generate/edit/{id}', [CompanyController::class, 'courseScheduleGenerateEdit'])->name('course-schedule-generate-edit');

        Route::get('/contacts/{contactId}', [ContactController::class, 'see_contacts'])->name('contacts_show');
        Route::get('/apply-tags/{contactId}/{tagId}', [ContactController::class, 'applyTags'])->name('apply_tag');
        Route::get('/tag-list/', [ContactController::class, 'tag_list'])->name('tag_list');
        Route::get('/campaign/create/group', [CampaignController::class, 'create_company'])->name('create_company');
        Route::get('/user/{userId}/create/campaign', [CampaignController::class, 'create_independent_user'])->name('create_independent_user');
        Route::get('/user/{userId}/tag-list', [CampaignController::class, 'listTag'])->name('user.tag-list');
    });

    Route::middleware([
        'auth', 'checkRole:editor,administrator'
    ])->group(function () {
        Route::resource('report', ReportController::class)->only('index', 'create', 'edit');
        Route::get('/report/company-{companyId}/season-{seasonId}', [ReportController::class, 'seasonCourseCompany'])->name('season-course-company');
        Route::get('/report/season-{seasonId}', [ReportController::class, 'seasonCourseEmployee'])->name('season-course-employee');
        Route::get('/report/season-{seasonId}/user-{userId}/course', [ReportController::class, 'seasonCourseIndex'])->name('season-course-index');
        Route::get('/report/season-{seasonId}/user-{userId}/form-{formId}/entry-{entryId}/detail', [ReportController::class, 'courseDetail'])->name('course-detail');

        Route::get('/export-user', [ExportController::class, 'exportToCSV'])->name('export-user');
        Route::get('/template-download', [ExportController::class, 'downloadTemplate'])->name('template-download');
        Route::get('/import-user', [ImportController::class, 'importIndex'])->name('to-import-user');
        Route::post('/import-user-action', [ImportController::class, 'importCSV'])->name('import-user');

    });
    Route::get('company/{id}', [CompanyController::class, 'show'])->name('company.show');
    Route::get('/company/{id}/add-employee', [CompanyController::class, 'addEmployee'])->name('company.add-employee');
    Route::get('/company/{id}/edit-employee/{employee}', [CompanyController::class, 'editEmployee'])->name('company.edit-employee');

    Route::get('/company/{id}/progress', [CompanyController::class, 'progress'])->name('company.progress');
    Route::get('/company/{id}/schedule', [CompanyController::class, 'schedule'])->name('company.schedule');
    Route::get('/company/{id}/schedule/create', [CompanyController::class, 'scheduleCreate'])->name('company.schedule-create');

    Route::get('/company/{id}/schedule/user/{user}', [CompanyController::class, 'scheduleUser'])->name('company.schedule-user');

    Route::get('/company/{id}/result/user/{user}', [CompanyController::class, 'resultUser'])->name('company.result-user');

});


Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

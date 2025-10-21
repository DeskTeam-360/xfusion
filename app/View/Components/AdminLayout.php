<?php

namespace App\View\Components;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Component;
use Illuminate\View\View;

class AdminLayout extends Component
{
    public $sidebar;
    public $navbars;
    public $notifications;
    public $logoLight;
    public $logoDark;

    public function __construct()
    {

        $this->logoLight = asset('assets/images/logos/light-logo.webp');
        $this->logoDark = asset('assets/images/logos/dark-logo.webp');

        $user = Auth::user();
        $company = $user->meta->where('meta_key', '=', 'company')->first();

        $user = Auth::user();
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }

        //
        if ($role != "contributor" && $role != "administrator" && $company!=null) {
            $companyId = $company['meta_value'];
            $this->logoLight = Storage::url(Company::find($companyId)->logo_url);
            $this->logoDark = Storage::url(Company::find($companyId)->logo_url);
        }



        $this->navbars = [
//            [
//                'title' => 'Apps',
//                'type' => 'dropdown',
//                'app_list_two_side' => true,
//                'quick_links' => [
//                    ['title' => 'title 1', 'route' => '#',],
//                    ['title' => 'title 2', 'route' => '#',],
//                ],
//                'app_lists_left' => [
//                    ['title' => 'title 11', 'sub-title' => 'sub title 1', 'icon_links' => '', 'route' => '#',],
//                    ['title' => 'title 12', 'sub-title' => 'sub title 1', 'icon_links' => '', 'route' => '#',],
//                    ['title' => 'title 13', 'sub-title' => 'sub title 1', 'icon_links' => '', 'route' => '#',],
//                ],
//                'app_lists_right' => [
//                    ['title' => 'title 21', 'sub-title' => 'sub title 2', 'icon_links' => '', 'route' => '#',],
//                    ['title' => 'title 22', 'sub-title' => 'sub title 2', 'icon_links' => '', 'route' => '#',],
//                    ['title' => 'title 23', 'sub-title' => 'sub title 2', 'route' => '#',],
//                ]
//            ],
//            ['title' => 'Chat', 'type' => 'link', 'route' => '#', 'icon' => 'ti ti-api-app'],
        ];

        $this->sidebar = [
            [
                'title' => 'Home',
                'lists' => [
                    ['title' => 'Dashboard', 'type' => 'link', 'route' => route('dashboard'), 'icon' => '<i class="ti ti-brand-chrome  text-xl flex-shrink-0"></i> '],
                ]
            ],
        ];

        $user = Auth::user();
        $ru = $user->meta->where('meta_key', '=', config('app.wp_prefix', 'wp_') . 'capabilities');
        $role = '';
        foreach ($ru as $r) {
            $role = array_key_first(unserialize($r['meta_value']));
        }
        if ($role == 'administrator') {
            $this->sidebar[0]['lists'][] = ['title' => 'Companies', 'type' => 'link', 'route' => route('company.index'), 'icon' => '<i class="ti ti-apps  text-xl flex-shrink-0"></i> '];
            $this->sidebar[0]['lists'][] = ['title' => 'Users', 'type' => 'link', 'route' => route('user.index'), 'icon' => '<i class="ti ti-users  text-xl flex-shrink-0"></i> '];
            $this->sidebar[0]['lists'][] = ['title' => 'User Progress Only', 'type' => 'link', 'route' => route('user-progress-only'), 'icon' => '<i class="ti ti-users  text-xl flex-shrink-0"></i> '];
            $this->sidebar[0]['lists'][] = ['title' => 'User-role', 'type' => 'link', 'route' => route('user.roles'), 'icon' => '<i class="ti ti-users  text-xl flex-shrink-0"></i> '];
            $this->sidebar[0]['lists'][] = ['title' => 'Course Group', 'type' => 'link', 'route' => route('course-group.index'), 'icon' => '<i class="ti ti-webhook  text-xl flex-shrink-0"></i> '];
            $this->sidebar[0]['lists'][] = ['title' => 'Course list', 'type' => 'link', 'route' => route('course-title.index'), 'icon' => '<i class="ti ti-webhook  text-xl flex-shrink-0"></i> '];
//            $this->sidebar[0]['lists'][] = ['title' => 'All Schedule', 'type' => 'link', 'route' => route('schedule-all'), 'icon' => '<i class="ti ti-clock  text-xl flex-shrink-0"></i> '];
            //$this->sidebar[0]['lists'][] = ['title' => 'Schedule generate', 'type' => 'link', 'route' => route('course-schedule-generate'), 'icon' => '<i class="ti ti-template  text-xl flex-shrink-0"></i> '];
            $this->sidebar[0]['lists'][] = ['title' => 'Report', 'type' => 'link', 'route' => route('report.index'), 'icon' => '<i class="ti ti-report  text-xl flex-shrink-0"></i> '];
            $this->sidebar[0]['lists'][] = ['title' => 'Campaign', 'type' => 'link', 'route' => route('campaign.index'), 'icon' => '<i class="ti ti-brand-campaignmonitor  text-xl flex-shrink-0"></i> '];
            $this->sidebar[0]['lists'][] = ['title' => 'Tags', 'type' => 'link', 'route' => route('tag.index'), 'icon' => '<i class="ti ti-tags  text-xl flex-shrink-0"></i> '];

        }
        if ($role == 'editor') {
            $companies = $user->meta->where('meta_key', '=', 'company');
            foreach ($companies as $r) {
                $c = Company::find($r['meta_value']);
                if ($c != null) {
                    $this->sidebar[0]['lists'][] = ['title' => 'Employee List', 'type' => 'link', 'route' => route('company.show', $c->id), 'icon' => '<i class="ti ti-users  text-xl flex-shrink-0"></i> '];
//                    $this->sidebar[0]['lists'][] = ['title' => 'Employee Progress', 'type' => 'link', 'route' => route('company.progress', $c->id), 'icon' => '<i class="ti ti-progress  text-xl flex-shrink-0"></i> '];
                    $this->sidebar[0]['lists'][] = ['title' => 'Active Schedule', 'type' => 'link', 'route' => route('company.schedule', $c->id), 'icon' => '<i class="ti ti-clock  text-xl flex-shrink-0"></i> '];
//                    $this->sidebar[0]['lists'][] = ['title' => 'CourseGroup', 'type' => 'link', 'route' => route('report.index'), 'icon' => '<i class="ti ti-user  text-xl flex-shrink-0"></i> '];
                    $this->sidebar[0]['lists'][] = ['title' => 'Report', 'type' => 'link', 'route' => route('report.index'), 'icon' => '<i class="ti ti-report  text-xl flex-shrink-0"></i> '];
                }
            }
        }
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.admin');
    }
}

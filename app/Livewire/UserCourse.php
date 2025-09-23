<?php

namespace App\Livewire;

use App\Models\CourseGroupDetail;
use App\Models\CourseList;
use App\Models\User;
use App\Models\WpGfEntry;
use App\Models\WpPost;
use Livewire\Component;

class UserCourse extends Component
{
    public $user;
    public $u;
    public $value;
    public $courseUser;
//    public $search = [];

    public $search = '';

    public function mount()
    {
        $this->u = User::find($this->user)->meta->where('meta_key', '=', '_sfwd-course_progress')->first();

        if ($this->u != null) {
            $this->value = $this->u->meta_value;
        } else {
            $this->redirect(route('user.index'));
        }

        $this->courseUser = unserialize($this->value);
//        $this->sortingResults();
    }

    public function getFilteredCourseUserProperty()
    {
        $search = strtolower($this->search);

        return collect($this->courseUser)->map(function ($lessons, $lessonId) use ($search) {
            $filteredTopics = [];

            foreach ($lessons['topics'] as $courseId => $topics) {
                foreach ($topics as $topicId => $value) {
                    $courseTitle = strtolower(WpPost::find($courseId)->post_title);
                    $topicTitle = strtolower(WpPost::find($topicId)->post_title);
                    if ($value == 1 && (str_contains($courseTitle, $search) || str_contains($topicTitle, $search))) {
                        $url = $this->getUrl($topicId);
                        $filteredTopics[$courseId][$topicId] = [
                            'value' => $value,
                            'url' => $url['url'],
                            'orders' => $url['orders'],
                            'date_created' => $url['date_created']
                        ];
                    }

                }
            }

            // 🔽 Sort each course's topics by 'orders'
            foreach ($filteredTopics as $courseId => &$topics) {
                uasort($topics, function ($a, $b) {
                    return $a['orders'] <=> $b['orders'];
                });
            }
//            dd($filteredTopics);
            return ['topics' => $filteredTopics];
        })->filter(function ($item) {
            return count($item['topics']);
        });
    }



    public function removeProgress($lessonId, $courseId, $topicId)
    {
        $wp = WpPost::find($topicId);
        $url = "%/topics/$wp->post_name/";
        $cl = CourseList::where('url', 'like', $url)->first();
        try {
            WpGfEntry::where('source_url', 'like', $url)->where('created_by', '=', $this->user)
            ->update([
                'status' => 'trash',
            ]);
            WpGfEntry::where('form_id', 'like', $cl->wp_gf_form_id)->where('created_by', '=', $this->user)
            ->update([
                'status' => 'trash',
            ]);
        } catch (\Throwable $th) {
            //throw $th;
        }
        
        $this->courseUser[$lessonId]['topics'][$courseId][$topicId] = 0;
        $this->u->update([
            'meta_value' => serialize($this->courseUser),
        ]);


        $this->dispatch('swal:alert', data: [
            'icon' => 'success',
            'title' => 'Delete progress user',
        ]);
    }

    public function getUrl($topicId)
    {
        $wp = WpPost::find($topicId);
        $url = "%/topics/$wp->post_name/";
        $cl = CourseList::where('url', 'like', $url)->first();
        $id='Invalid Progress';
        if ($cl!=null) {


            $wf1 = WpGfEntry::where('source_url', 'like', $url)->where('created_by', '=', $this->user)->where('status','active')->first();
//            dd($wf1);

            if ($wf1 != null) {
                $id = $wf1;
            } else {
                if ($cl->wp_gf_form_id!=null) {
                    $wf2 = WpGfEntry::where('form_id', 'like', $cl->wp_gf_form_id)->where('created_by', $this->user)->where('status','active')->first();
                    if ($wf2 != null) {
                        $id = $wf2;
                    }
                }
            }
            if ($id == 'Invalid Progress') {
                $id = 'Invalid Progress';
            }else{
                $id=$id->date_created;
            }

            $cgd= CourseGroupDetail::where('course_list_id',$cl->id)->first();

            if ($cgd!=null){
                return ['url'=>$cl->url,'orders'=>($cgd->course_group_id*2000)+$cgd->orders,'date_created'=>$id];
            }
        }
        return ['url'=>'','orders'=>999,'date_created'=>$id];


    }


    public function sortingResults()
    {
        dd($this->getFilteredCourseUserProperty());
    }

    public function redirectToCourse($lessonId, $courseId, $topicId)
    {

        $wp = WpPost::find($topicId);
        $url = "%/topics/$wp->post_name/";
        $cl = CourseList::where('url', 'like', $url)->first();
        $id = null;
        $wf1 = WpGfEntry::where('source_url', 'like', $url)->where('created_by', '=', $this->user)->where('status','active')->first();

        if ($wf1 != null) {
            $id = $wf1->id;
        } else {
            $wf2 = WpGfEntry::where('form_id', 'like', $cl->wp_gf_form_id)->where('created_by', '=', $this->user)
                >where('status','active')->first();
            if ($wf2 != null) {
                $id = $wf2->id;
            }
        }
        if ($id == null) {
            $this->dispatch('swal:alert', data: [
                'icon' => 'warning',
                'title' => 'Invalid progress',
            ]);
        } else {
            $this->dispatch('swal:redirect:new-tab', data:[
                'url' => $cl->url . '?dataId=' . $id,
            ]);
        }


    }

    public function render()
    {
        return view('livewire.user-course');
    }
}

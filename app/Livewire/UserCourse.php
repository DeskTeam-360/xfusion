<?php

namespace App\Livewire;

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
    public $search = [];

    public function mount()
    {
        $this->u = User::find($this->user)->meta->where('meta_key', '=', '_sfwd-course_progress')->first();

        if ($this->u != null) {
            $this->value = $this->u->meta_value;
        } else {
            $this->redirect(route('user.index'));
        }

        $this->courseUser = unserialize($this->value);
    }

    public function removeProgress($lessonId, $courseId, $topicId)
    {


        $wp = WpPost::find($topicId);

        $url = "%/topics/$wp->post_name%";
        $cl = CourseList::where('url', 'like', $url)->first();

        WpGfEntry::where('source_url', 'like', $url)->where('created_by', '=', $this->user)
            ->update([
                'status' => 'trash',
            ]);
        WpGfEntry::where('form_id', 'like', $cl->wp_gf_form_id)->where('created_by', '=', $this->user)
            ->update([
                'status' => 'trash',
            ]);


        $this->courseUser[$lessonId]['topics'][$courseId][$topicId] = 0;
        $this->u->update([
            'meta_value' => serialize($this->courseUser),
        ]);


        $this->dispatch('swal:alert', data: [
            'icon' => 'success',
            'title' => 'Delete progress user',
        ]);
    }

    public function redirectToCourse($lessonId, $courseId, $topicId)
    {

        $wp = WpPost::find($topicId);

        $url = "%/topics/$wp->post_name%";
        $cl = CourseList::where('url', 'like', $url)->first();
        $id = null;
        $wf1 = WpGfEntry::where('source_url', 'like', $url)->where('created_by', '=', $this->user)
            ->first();
        if ($wf1 != null) {
            $id = $wf1->id;
        } else {
            $wf2 = WpGfEntry::where('form_id', 'like', $cl->wp_gf_form_id)->where('created_by', '=', $this->user)
                ->first();
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
            $this->redirect($cl->url . '?dataId=' . $id);
        }


    }

    public function render()
    {
        return view('livewire.user-course');
    }
}

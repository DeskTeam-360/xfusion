<?php

namespace App\Livewire\Form;

use App\Models\CourseScheduleGenerate;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ScheduleGenerate extends Component
{

    #[Validate('required')]
    public $course_list_id;
    #[Validate('required')]
    public $course_list_parent_id;

    #[Validate('required')]
    public $week;

    public $action;
    public $dataId;

    public $optionCourseList;
    public function mount()
    {
        $this->week = 0;
        $this->optionCourseList = [];
        foreach (\App\Models\CourseList::get() as $item){
            $this->optionCourseList[] =['value'=>$item->id,'title'=>"$item->page_title - $item->course_title"];
        }
        if ($this->dataId!=null){
            $data = CourseScheduleGenerate::find($this->dataId);
            $this->course_list_id = $data->course_list_id;
            $this->course_list_parent_id = $data->course_list_parent_id;
            $this->week = $data->week;
        }
    }

    public function create()
    {
        $this->validate();
        $this->resetErrorBag();
        \App\Models\CourseScheduleGenerate::create([
            'course_list_id'=>$this->course_list_id,
            'course_list_parent_id'=>$this->course_list_parent_id,
            'week'=>$this->week
        ]);

        $this->redirect(route('course-schedule-generate'));
    }
    public function update()
    {
        $this->validate();
        $this->resetErrorBag();
        \App\Models\CourseScheduleGenerate::find($this->dataId)->update([
            'course_list_id'=>$this->course_list_id,
            'course_list_parent_id'=>$this->course_list_parent_id,
            'week'=>$this->week
        ]);

        $this->redirect(route('course-schedule-generate'));
    }
    public function render()
    {
        return view('livewire.form.schedule-generate');
    }
}

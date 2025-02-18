<?php

namespace App\Livewire\Form;

use Livewire\Component;

class CourseGroup extends Component
{
    public $action = 'create';
    public $title='';
    public $subTitle='';
    public $type='';
    public $dataId;
public $optionCourseTitle;
public $optionCourseType;
public $courseLists;
    public function getRules()
    {
        return [
            'title' => 'required|string|max:255',
            'subTitle' => 'required|string|max:255',
            'type' => 'required|string|max:255',
        ];
    }

    public function mount(){
        $this->courseLists=[];
        $this->optionCourseType=[
            ['value'=>'revitalize','title'=>'Revitalize'],
            ['value'=>'sustain','title'=>'Sustain'],
            ['value'=>'transform','title'=>'Transform'],
        ];

        $this->optionCourseTitle = [];
        foreach (\App\Models\CourseList::get() as $cl){
            $this->optionCourseTitle[] = ['value'=>$cl->id,'title'=>$cl->title];
        }

        if($this->dataId!=null){
            $data = \App\Models\CourseGroup::find($this->dataId);
            $this->courseLists=$data->courseGroupDetails->pluck('course_list_id')->toArray();
            $this->title = $data->title;
            $this->subTitle = $data->sub_title;
            $this->type = $data->type;
        }
    }

    public function create(){
        $this->validate();
        $this->resetErrorBag();

        $cg=\App\Models\CourseGroup::create([
            'title' => $this->title,
            'sub_title' => $this->subTitle,
            'type' => $this->type,
        ]);

        foreach($this->courseLists as $cl){
            \App\Models\CourseGroupDetail::create([
                'course_list_id' => $cl,
                'course_group_id' => $cg->id,
            ]);
        }



        $this->dispatch('swal:alert', data: [
            'icon' => 'success',
            'title' => 'Successfully added course group',
        ]);
        $this->redirect(route('course-group.index'));
    }
    public function update(){
        $this->validate();
        $this->resetErrorBag();

        \App\Models\CourseGroup::find($this->dataId)->update([
            'title' => $this->title,
            'sub_title' => $this->subTitle,
            'type' => $this->type,
        ]);

        \App\Models\CourseGroupDetail::where('course_group_id', $this->dataId)->delete();

        foreach($this->courseLists as $cl){
            \App\Models\CourseGroupDetail::create([
                'course_list_id' => $cl,
                'course_group_id' => $this->dataId,
            ]);
        }

        $this->dispatch('swal:alert', data: [
            'icon' => 'success',
            'title' => 'Successfully edited course group',
        ]);
        $this->redirect(route('course-group.index'));
    }
    public function render()
    {
        return view('livewire.form.course-group');
    }
}

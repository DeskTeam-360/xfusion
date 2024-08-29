<?php

namespace App\Livewire\Form;


use Livewire\Component;

class CourseList extends Component
{
    public $action="create";
    public $dataId;

    public $url;
    public $pageTitle;
    public $courseTitle;
    public $optionCourseTitle;

    public function getRules()
    {
        return [
            'url'=>'required|max:255',
            'pageTitle'=>'required|max:255',
            'courseTitle'=>'required|max:255',
        ];
    }
    public function mount()
    {
        $this->optionCourseTitle = [
            ['value' => 'Revitalize','title'=>'Revitalize'],
            ['value' => 'Sustain','title'=>'Sustain'],
            ['value' => 'Transform','title'=>'Transform'],
        ];
        if ($this->dataId!=null){
            $data = \App\Models\CourseList::find($this->dataId);
            $this->url=$data->url;
            $this->pageTitle=$data->page_title;
            $this->courseTitle=$data->course_title;
        }
    }

    public function create()
    {
        $this->validate();
        $this->resetErrorBag();
        \App\Models\CourseList::create([
            'url'=>$this->url,
            'page_title'=>$this->pageTitle,
            'course_title'=>$this->courseTitle,
        ]);
        $this->redirect(route('course-title.index'));
    }

    public function update()
    {
        $this->validate();
        $this->resetErrorBag();
        \App\Models\CourseList::find($this->dataId)->update([
            'url'=>$this->url,
            'page_title'=>$this->pageTitle,
            'course_title'=>$this->courseTitle,
        ]);
        $this->redirect(route('course-title.index'));
    }

    public function render()
    {
        return view('livewire.form.limit-link');
    }
}

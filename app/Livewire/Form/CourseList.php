<?php

namespace App\Livewire\Form;


use App\Models\Tag;
use Livewire\Component;

class CourseList extends Component
{
    public $action="create";
    public $dataId;

    public $url;
    public $pageTitle;
    public $courseTitle;
    public $courseTag;
    public $optionCourseTitle;
    public $optionCourseTag;

    public function getRules()
    {
        return [
            'url'=>'required|max:255',
            'pageTitle'=>'required|max:255',
            'courseTitle'=>'required|max:255',
            'courseTag'=>'nullable',
        ];
    }
    public function mount()
    {
        $this->optionCourseTitle = [
            ['value' => 'General','title'=>'General'],
            ['value' => 'Revitalize','title'=>'Revitalize'],
            ['value' => 'Sustain','title'=>'Sustain'],
            ['value' => 'Transform','title'=>'Transform'],
        ];

        $this->optionCourseTag = [];
        foreach(Tag::get() as $item){
            $this->optionCourseTag [] = ['value' => $item->id, 'title' => $item->name];
        }
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
        $this->dispatch('swal:alert', data:[
            'icon' => 'success',
            'title' => 'Successfully added course',
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
        $this->dispatch('swal:alert', data:[
            'icon' => 'success',
            'title' => 'successfully changed the course',
        ]);
        $this->redirect(route('course-title.index'));
    }

    public function render()
    {
        return view('livewire.form.limit-link');
    }
}

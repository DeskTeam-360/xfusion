<?php

namespace App\Livewire\Form;


use App\Models\Tag;
use App\Models\WpGfForm;
use Livewire\Component;

class CourseList extends Component
{
    public $action = "create";
    public $dataId;

    public $url;
    public $pageTitle;
    public $courseTitle;
    public $gfFormId;
    public $urlNext;
    public $courseTag = null;
    public $courseTagNext = null;
    public $delay = 10;
    public $optionCourseTitle;
    public $optionCourseTag;
    public $optionWpGfForm;
    public $repeatEntry=0;

    public function getRules()
    {
        return [
            'url' => 'required|max:255',
            'pageTitle' => 'required|max:255',
            'courseTitle' => 'required|max:255',
            'courseTag' => 'nullable',
        ];
    }

    public function mount()
    {
        $this->optionCourseTitle = [
            ['value' => 'General', 'title' => 'General'],
            ['value' => 'Revitalize', 'title' => 'Revitalize'],
            ['value' => 'Revitalize Tools', 'title' => 'Revitalize Tools'],
            ['value' => 'Revitalize Resources', 'title' => 'Revitalize Resources'],

            ['value' => 'Transform', 'title' => 'Transform'],
            ['value' => 'Transform Tools', 'title' => 'Transform Tools'],
            ['value' => 'Transform Resources', 'title' => 'Transform Resources'],

            ['value' => 'Sustain', 'title' => 'Sustain'],
            ['value' => 'Sustain Tools', 'title' => 'Sustain Tools'],
            ['value' => 'Sustain Resources', 'title' => 'Sustain Resources'],
        ];

        $this->optionCourseTag = [];
        foreach (Tag::get() as $item) {
            $this->optionCourseTag [] = ['value' => $item->id, 'title' => $item->name];
        }

        $this->optionWpGfForm = [];
        foreach (WpGfForm::get() as $item) {
            $this->optionWpGfForm [] = ['value' => $item->id, 'title' =>$item->id.' - '. $item->title];
        }
        if ($this->dataId != null) {
            $data = \App\Models\CourseList::find($this->dataId);
            $this->url = $data->url;
            $this->pageTitle = $data->page_title;
            $this->courseTitle = $data->course_title;
            $this->courseTag = $data->keap_tag;
            $this->courseTagNext = $data->keap_tag_next;
            $this->gfFormId = $data->wp_gf_form_id;
            $this->delay = $data->delay;
            $this->urlNext = $data->url_next;
        }
    }

    public function create()
    {
        $this->validate();
        $this->resetErrorBag();

        // Log::info($this->courseTag);
        if (isset($this->courseTag[0])){
            $this->courseTag = $this->courseTag[0];
        }else{
            $this->courseTag = null;
        }
        if (isset($this->courseTagNext[0])){
            $this->courseTagNext = $this->courseTagNext[0];
        }else{
            $this->courseTagNext = null;
        }

        \App\Models\CourseList::create([
            'url' => $this->url,
            'page_title' => $this->pageTitle,
            'course_title' => $this->courseTitle,
            'wp_gf_form_id'=>$this->gfFormId,
            'keap_tag'=>$this->courseTag,
            'keap_tag_next' => $this->courseTagNext,
            'delay'=>10,
            'url_next'=>$this->urlNext,
            'repeat_entry'=>$this->repeatEntry,
        ]);
        $this->dispatch('swal:alert', data: [
            'icon' => 'success',
            'title' => 'Successfully added course',
        ]);
        $this->redirect(route('course-title.index'));
    }

    public function update()
    {
        $this->validate();
        $this->resetErrorBag();
        if (isset($this->courseTag[0]) || $this->courseTag != null){
            $this->courseTag = $this->courseTag[0]??$this->courseTag;
        }else{
            $this->courseTag = null;
        }
        if (isset($this->courseTagNext[0]) || $this->courseTagNext != null){
            $this->courseTagNext = $this->courseTagNext[0]??$this->courseTagNext;
        }else{
            $this->courseTagNext = null;
        }


        \App\Models\CourseList::find($this->dataId)->update([
            'url' => $this->url,
            'page_title' => $this->pageTitle,
            'course_title' => $this->courseTitle,
            'wp_gf_form_id'=>$this->gfFormId,
            'keap_tag'=>$this->courseTag,
            'keap_tag_next' => $this->courseTagNext,
            'delay'=>$this->delay,
            'url_next'=>$this->urlNext
        ]);
        $this->dispatch('swal:alert', data: [
            'icon' => 'success',
            'title' => 'successfully changed the course',
        ]);
        $this->redirect(route('course-title.index'));
    }

    public function render()
    {
        return view('livewire.form.course-list');
    }
}

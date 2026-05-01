<?php

namespace App\Livewire\Form;

use App\Models\Tag;
use App\Models\WpGfForm;
use App\Models\WpPost;
use Illuminate\Validation\Rule;
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
    public $repeatEntry = 0;

    public $legacy = 0;

    /** @var int|null wp_posts.ID for LearnDash topic (post_type sfwd-topic) */
    public $lmsTopicId = null;

    public string $lmsTopicSearch = '';

    /** @var array<int, array{value: int, title: string}> */
    public array $lmsTopicResults = [];

    public string $lmsTopicSelectedLabel = '';

    public function getRules()
    {
        return [
            'url' => 'required|max:255',
            'pageTitle' => 'required|max:255',
            'courseTitle' => 'required|max:255',
            'courseTag' => 'nullable',
            'lmsTopicId' => [
                'nullable',
                'integer',
                Rule::exists('wp_posts', 'ID')->where('post_type', 'sfwd-topic'),
            ],
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
            $this->repeatEntry = $data->repeat_entry;
            $this->legacy = $data->legacy;
            $this->lmsTopicId = $data->lms_topic_id;
            $this->refreshLmsTopicLabel();
        }
    }

    public function updatedLmsTopicSearch(string $value): void
    {
        $this->lmsTopicResults = [];
        $keyword = trim($value);
        if (strlen($keyword) < 2) {
            return;
        }

        $escaped = addcslashes($keyword, '%_\\');
        $like = '%'.$escaped.'%';

        $this->lmsTopicResults = WpPost::query()
            ->where('post_type', 'sfwd-topic')
            ->where('post_status', 'publish')
            ->where('post_title', 'like', $like)
            ->orderBy('post_title')
            ->limit(30)
            ->get(['ID', 'post_title'])
            ->map(fn (WpPost $p) => [
                'value' => (int) $p->ID,
                'title' => $p->post_title.' (ID: '.$p->ID.')',
            ])
            ->all();
    }

    public function selectLmsTopic(int $id): void
    {
        $post = WpPost::query()
            ->where('ID', $id)
            ->where('post_type', 'sfwd-topic')
            ->first(['ID', 'post_title']);

        if (! $post) {
            return;
        }

        $this->lmsTopicId = $id;
        $this->lmsTopicSelectedLabel = $post->post_title.' (ID: '.$post->ID.')';
        $this->lmsTopicSearch = '';
        $this->lmsTopicResults = [];
    }

    public function clearLmsTopic(): void
    {
        $this->lmsTopicId = null;
        $this->lmsTopicSelectedLabel = '';
        $this->lmsTopicSearch = '';
        $this->lmsTopicResults = [];
    }

    private function refreshLmsTopicLabel(): void
    {
        if (! $this->lmsTopicId) {
            $this->lmsTopicSelectedLabel = '';

            return;
        }

        $post = WpPost::query()
            ->where('ID', $this->lmsTopicId)
            ->where('post_type', 'sfwd-topic')
            ->first(['ID', 'post_title']);

        $this->lmsTopicSelectedLabel = $post
            ? $post->post_title.' (ID: '.$post->ID.')'
            : '';
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
            'wp_gf_form_id' => $this->gfFormId,
            'lms_topic_id' => $this->lmsTopicId,
            'keap_tag' => $this->courseTag,
            'keap_tag_next' => $this->courseTagNext,
            'delay' => 10,
            'url_next' => $this->urlNext,
            'repeat_entry' => $this->repeatEntry,
            'legacy' => $this->legacy,
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
            'wp_gf_form_id' => $this->gfFormId,
            'lms_topic_id' => $this->lmsTopicId,
            'keap_tag' => $this->courseTag,
            'keap_tag_next' => $this->courseTagNext,
            'delay' => $this->delay,
            'url_next' => $this->urlNext,
            'repeat_entry' => $this->repeatEntry,
            'legacy' => $this->legacy,
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

<?php

namespace App\Livewire\Form;

use Livewire\Component;

class CourseGroupDetail extends Component
{
    public $dataId;
    public $courseGroup;
    public $orders;
    public function mount(){
        $courseGroup = \App\Models\CourseGroup::find($this->dataId);
        foreach($courseGroup->courseGroupDetails as $course){
            $this->orders[$course->id] = $course->orders;
        }
    }
    public function setChangeOrder($id)
    {
        $order=$this->orders[$id];
        if (is_numeric($order)) {
            \App\Models\CourseGroupDetail::find($id)->update(['orders' => $order]);
            $this->dispatch('swal:alert', data: [
                'icon' => 'success',
                'title' => 'Successfully added course group',
            ]);
        }
    }
    public function render()
    {
        return view('livewire.form.course-group-detail');
    }
}

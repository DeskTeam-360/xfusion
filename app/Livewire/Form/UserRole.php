<?php

namespace App\Livewire\Form;

use Livewire\Attributes\Validate;
use Livewire\Component;

class UserRole extends Component
{
    #[Validate('required|max:255')]
    public $title;
    public $optionRoles;
    public function mount()
    {
        $this->optionRoles =[
            ['value'=>'revitalize','title'=>'Revitalize'],
            ['value'=>'revitalize-facilitation','title'=>'Revitalize facilitation'],

            ['value'=>'transform','title'=>'Transform'],
            ['value'=>'transform-resource','title'=>'Transform resource'],
            ['value'=>'transform-tools','title'=>'Transform tools'],

            ['value'=>'sustain','title'=>'Sustain'],
            ['value'=>'sustain-resource','title'=>'Sustain resource'],
            ['value'=>'sustain-tools','title'=>'Sustain tools'],
            ['value'=>'individual-reports','title'=>'Individual reports'],
        ];
    }
    public function render()
    {
        return view('livewire.form.user-role');
    }
}

<?php

namespace App\Livewire;

use App\Models\WpGfEntry;
use App\Models\WpGfForm;
use Livewire\Component;

class Revitalize extends Component
{
    public $entries;

    public function mount()
    {
        $this->entries =  WpGfEntry::whereIn('form_id',[2,3,4,7,8,9,10,11,12,13,14,15,16,17,18,19,24,25,26,27,28,29,30])->get();
    }
    public function render()
    {
        return view('livewire.revitalize');
    }
}

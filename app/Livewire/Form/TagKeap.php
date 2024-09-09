<?php

namespace App\Livewire\Form;

use App\Models\Tag;
use KeapGeek\Keap\Exceptions\BadRequestException;
use KeapGeek\Keap\Facades\Keap;
use Livewire\Attributes\Validate;
use Livewire\Component;

class TagKeap extends Component
{
    public $action="create";
    public $dataId;

    #[Validate('required|max:255')]
    public $name;
    public $description;

    public function mount()
    {
        $this->name = '';
        $this->description = '';
        if ($this->dataId != null) {
            $data = Tag::findOrFail($this->dataId);
            $this->name = $data->name;
            $this->description = $data->description;
        }
    }

    public function create()
    {
        $this->validate();
        $this->resetErrorBag();

        try {
            $keap = Keap::tag()->create([
                'name' => $this->name,
                'description' => $this->description,
                'category_id' => config('app.keap_category'),
            ]);
            Tag::create([
                'id'=>$keap['id'],
                'name'=>$this->name,
                'description'=>$this->description,
                'category'=>config('app.keap_category'),
            ]);
        }catch (BadRequestException $badRequestException){
dd($badRequestException);
        }

    }

    public function render()
    {
        return view('livewire.form.tag-keap');
    }
}

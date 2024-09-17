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
//        dd();
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

//        dd($this->dataId);
        try {
            $keap = Keap::tag()->create([
                'name' => $this->name,
                'description' => $this->description,
                'category_id' => config('app.keap_category'),
            ]);
//            dd($keap['id']);
            Tag::create([
                'id'=>$keap['id'],
                'name'=>$this->name,
                'description'=>$this->description,
                'category'=>config('app.keap_category'),
            ]);
        }catch (BadRequestException $badRequestException){
            dd($badRequestException);
        }
        $this->dispatch('swal:alert', data:[
            'icon' => 'success',
            'title' => 'Successfully added tag',
        ]);

    }

    public function update()
    {
        $this->validate();
        $this->resetErrorBag();

//        dd(Keap::tag()->find($this->dataId));

        try {
            Keap::tag()->update([
                'name' => $this->name,
                'description' => $this->description,
            ]);

            Tag::find($this->dataId)->update([
                'name'=>$this->name,
                'description'=>$this->description,
            ]);

        }catch (BadRequestException $badRequestException){
            dd($badRequestException);
        }


        $this->dispatch('swal:alert', data:[
            'icon' => 'success',
            'title' => 'Successfully updated tag',
        ]);
        $this->redirect(route('tag.index'));
    }

    public function render()
    {
        return view('livewire.form.tag-keap');
    }
}

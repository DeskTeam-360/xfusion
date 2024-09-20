<?php

namespace App\Livewire\Form;

use App\Models\Tag;
use Illuminate\Support\Str;
use KeapGeek\Keap\Exceptions\BadRequestException;
use KeapGeek\Keap\Facades\Keap;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mockery\Exception;

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
        $this->name = 'xfusion-';
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
        $name = str_replace('.',"-",$this->name);
        $name = Str::slug($name);
        $template = ['start','end','finish'];
//        sleep(10);
        try {
            foreach ($template  as $t){
                $keap = Keap::tag()->create([
                    'name' => $name."-".$t,
                    'description' => $this->description,
                    'category_id' => config('app.keap_category'),
                ]);
//                dd($keap);

                Tag::create([
                    'id'=>$keap['id'],
                    'name' => $name."-".$t,
                    'description'=>$this->description,
                    'category'=>config('app.keap_category'),
                ]);
            }
        }catch (BadRequestException $badRequestException){
            $this->dispatch('swal:alert', data:[
                'icon' => 'error',
                'title' => $badRequestException->getMessage(),
            ]);
        }
        catch (Exception $e){
            dd($keap);
            $this->dispatch('swal:alert', data:[
                'icon' => 'error',
                'title' => $e->getMessage(),
            ]);
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

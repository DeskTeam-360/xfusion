<?php

namespace App\Livewire\Form;

use App\Repository\View\CompanyEmployee;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;


class Company extends Component
{

    use WithFileUploads;

    public $action;
    public $dataId;
    public $data;
    public $usersOption;

    #[Validate('required')]
    public $user_id;
    #[Validate('required|max:255')]
    public $title;
    #[Validate('image:4096|mimes:png')]
    public $logo_url;
    #[Validate('image:4096|mimes:png')]
    public $qrcode_url;

    public function mount()
    {
        $this->usersOption = [];
        foreach (\App\Models\User::get() as $item) {
            $this->usersOption[] = ['value' => $item->ID, 'title' => $item->user_email];
        }
        if ($this->dataId) {
            $data = \App\Models\Company::find($this->dataId);
            $this->user_id = $data->user_id;
            $this->title = $data->title;
        }
    }

    public function create()
    {
        $this->validate();
        $this->resetErrorBag();

        if ($this->logo_url != null) {
            $logoUrl = $this->logo_url->store(path: 'public/photos');
        }
        if ($this->qrcode_url != null) {
            $qrcodeUrl = $this->qrcode_url->store(path: 'public/qrcode');
        }
        $company = \App\Models\Company::create([
            'user_id' => $this->user_id,
            'title' => $this->title,
            'logo_url' => $logoUrl,
            'qrcode_url' => $qrcodeUrl
        ]);
        \App\Models\User::find($this->user_id)
        ->saveMeta([
            'company'=>$company->id
        ]);
        CompanyEmployee::create([
           'user_id' => $this->user_id,
           'company_id' => $company->id
        ]);
        $this->dispatch('swal:alert', data:[
            'icon' => 'success',
            'title' => 'Successfully added company',
        ]);
        $this->redirect(route('company.index'));
    }

    public function update()
    {
        $this->validate();
        $this->resetErrorBag();
        if ($this->logo_url != null) {
            $logoUrl = $this->logo_url->store(path: 'public/photos');
            \App\Models\Company::find($this->dataId)->update([
                'logo_url' => $logoUrl,
            ]);
        }
        if ($this->qrcode_url != null) {
            $qrcodeUrl = $this->qrcode_url->store(path: 'public/qrcode');
            \App\Models\Company::find($this->dataId)->update([
                'qrcode_url' => $qrcodeUrl
            ]);
        }
        \App\Models\Company::find($this->dataId)->update([
            'user_id' => $this->user_id,
            'title' => $this->title,
        ]);
        $this->dispatch('swal:alert', data:[
            'icon' => 'success',
            'title' => 'successfully changed the company',
        ]);
        $this->redirect(route('company.index'));
    }

    public function render()
    {
        return view('livewire.form.company');
    }
}

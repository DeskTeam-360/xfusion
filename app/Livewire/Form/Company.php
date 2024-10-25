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

//    #[Validate('required')]
    public $user_id;
//    #[Validate('required|max:255')]
    public $title;
//    #[Validate('image:4096|mimes:png')]
    public $logo_url;
//    #[Validate('image:4096|mimes:png')]
    public $qrcode_url;

    protected $rules = [
        'title' => 'required|max:255',
        'logo_url' => 'required',
        'qrcode_url' => 'required',
        'user_id' => 'required',
    ];

    protected $messages = [
        'title.required' => 'Please fill the Company name.',
        'logo_url.required' => 'Please select a Company Logo.',
        'qrcode_url.required' => 'Please select a Company QRcode.',
        'user_id.required' => 'Please select a Company leader.',
    ];

    public function mount()
    {
        $this->usersOption = [];
        foreach (\App\Models\User::get() as $item) {
            $this->usersOption[] = ['value' => $item->ID, 'title' => $item->user_email];
        }

        if ($this->dataId) {
            $data = \App\Models\Company::find($this->dataId);
            $this->user_id = (int)$data->user_id;
            $this->title = $data->title;
            $this->logo_url = $data->logo_url;
            $this->qrcode_url = $data->qrcode_url;
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
        $this->validate([
            'user_id' => 'required',
            'title' => 'required|string|max:255',
        ]);
        $this->resetErrorBag();
        $data_compare = \App\Models\Company::find($this->dataId);

        if ($data_compare->logo_url != $this->logo_url) {
            if ($this->logo_url != null) {
                $logoUrl = $this->logo_url->store(path: 'public/photos');
                \App\Models\Company::find($this->dataId)->update([
                    'logo_url' => $logoUrl,
                ]);
            }
        } else if ($data_compare->qrcode_url != $this->qrcode_url) {
            if ($this->qrcode_url != null) {
                $qrcodeUrl = $this->qrcode_url->store(path: 'public/qrcode');
                \App\Models\Company::find($this->dataId)->update([
                    'qrcode_url' => $qrcodeUrl
                ]);
            }
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

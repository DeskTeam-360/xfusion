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
    public $company_url;

    protected $rules = [
        'title' => 'required|max:255',
        'logo_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096|dimensions:min_width=140,min_height=60,max_width=160,max_height=80',
        'company_url' => 'nullable',
        'user_id' => 'required',
    ];

    protected $messages = [
        'title.required' => 'Please fill the Company name.',
        'logo_url.image' => 'The logo must be an image file.',
        'logo_url.mimes' => 'The logo must be a file of type: jpeg, png, jpg, gif.',
        'logo_url.max' => 'The logo may not be greater than 4MB.',
        'logo_url.dimensions' => 'The logo must be between 140x60 and 160x80 pixels (recommended: 150x70).',
        'user_id.required' => 'Please select a Company leader.',
    ];

    public function mount()
    {
        $this->usersOption = [];
        // Only get users who don't have a company
        foreach (\App\Models\User::whereDoesntHave('companyEmployee')->get() as $item) {
            $this->usersOption[] = ['value' => $item->ID, 'title' => $item->user_email];
        }

        if ($this->dataId) {
            $data = \App\Models\Company::find($this->dataId);
            $this->user_id = (int)$data->user_id;
            $this->title = $data->title;
            $this->logo_url = $data->logo_url;
            $this->qrcode_url = $data->qrcode_url;
            $this->company_url = $data->company_url;
            $this->usersOption[] = ['value' => $data->user_id, 'title' => \App\Models\User::find($data->user_id)->user_email. " - Current Company Leader"];
        }
    }

    public function create()
    {
        $this->validate();

        if ($this->logo_url != null) {
            $logoUrl = $this->logo_url->store(path: 'public/photos');
        }
        if ($this->qrcode_url != null) {
            $qrcodeUrl = $this->qrcode_url->store(path: 'public/qrcode');
        }
        $company = \App\Models\Company::create([
            'user_id' => $this->user_id,
            'title' => $this->title,
            'logo_url' => $logoUrl??'',
            'qrcode_url' => $qrcodeUrl??'',
            'company_url' => $this->company_url
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
            'logo_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096|dimensions:min_width=140,min_height=60,max_width=160,max_height=80',
        ]);
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
            'company_url' => $this->company_url,
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

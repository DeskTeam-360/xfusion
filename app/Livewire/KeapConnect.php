<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\WpUserMeta;
use KeapGeek\Keap\Facades\Keap;
use Livewire\Component;

class KeapConnect extends Component
{
    public $userId;
    public $user;
    public $keap;
    public $firstName;
    public $lastName;


    public function mount()
    {
        $this->user = User::find($this->userId);
        $this->getDataKeap();

        foreach($this->user->meta->where('meta_key','first_name') as $name){
            $this->firstName = $name['meta_value'];
        }
        foreach($this->user->meta->where('meta_key','last_name') as $name){
            $this->lastName = $name['meta_value'];
        }


    }

    public function getDataKeap()
    {
        $this->keap = Keap::contact()->list([
            'email' => $this->user->email
        ]);
    }

    public function connect()
    {
        if ($this->keap != null) {
            foreach ($this->keap as $k) {
                WpUserMeta::create([
                    'meta_key' => 'keap_contact_id',
                    'user_id' => $this->userId,
                    'meta_value' => $k['id']
                ]);
                break;
            }
        } else {
            dd("masuk sini");
            Keap::contact()->create([
                'given_name'=>$this->firstName,
                'family_name'=>$this->lastName,
                'email'=>$this->user->email
            ]);
        }
    }

    public function render()
    {
        return view('livewire.keap-connect');
    }
}

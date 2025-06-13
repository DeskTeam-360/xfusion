<?php

namespace App\Livewire\Form;

use Carbon\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Component;
use MikeMcLin\WpPassword\Facades\WpPassword;

class ResetPassword extends Component
{

    public $dataId;
    public $user;
    #[Validate('required|max:255|min:8')]
    public $password;
    #[Validate('required|max:255|min:8|same:password')]
    public $rePassword;

    public function mount()
    {
        $this->user=\App\Models\User::find($this->dataId);
    }

    public function update()
    {
        $this->validate();
        $this->resetErrorBag();
        if ($this->password == $this->rePassword){
            $user = \App\Models\User::find($this->dataId)->update([
                'user_pass' => md5($this->password),
            ]);
        }
        $this->dispatch('swal:alert', data:[
            'icon' => 'success',
            'title' => 'password has been changed',
        ]);
        $this->redirect(route('user.index'));
    }

    public function render()
    {
        return view('livewire.form.reset-password');
    }
}

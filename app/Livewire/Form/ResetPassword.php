<?php

namespace App\Livewire\Form;

use Carbon\Carbon;
use KeapGeek\Keap\Facades\Keap;
use Livewire\Attributes\Validate;
use Livewire\Component;
use MikeMcLin\WpPassword\Facades\WpPassword;
use Hautelook\Phpass\PasswordHash;

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



            $hasher = new PasswordHash(8, true); // Sama seperti di WordPress
            $passwordHash = $hasher->HashPassword($this->password);

            $contact = Keap::contact()->createOrUpdate([

                'email_addresses' => [['email' => $this->user->email, 'field' => 'EMAIL1',],],
                'custom_fields' => [
                    ['id' => '96', 'content' => $this->user->email],
                                        ['id' => '98', 'content' => $this->password],
                ],
            ]);


//            User::create([
//                'email' => $request->email,
//                'password' => $passwordHash, // Ini format $P$... yang cocok buat WordPress
//            ]);



            $user = \App\Models\User::find($this->dataId)->update([
                'user_pass' => $passwordHash,
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

<?php

namespace App\Livewire;

use Livewire\Component;
use MikeMcLin\WpPassword\Facades\WpPassword;

class Testing extends Component
{
    public function mount()
    {

//        $a = 'a:6:{s:4:"time";i:1714638481;s:5:"fonts";a:1:{i:0;s:8:"gellatio";}s:5:"icons";a:1:{i:0;s:0:"";}s:20:"dynamic_elements_ids";a:0:{}s:6:"status";s:4:"file";i:0;s:0:"";}';
//        dd(unserialize($a));
//dd(base64_encode(json_encode($a)));


//        $role{"asdasd"}
//        $a=['contributor'=>true];
//        dd('a:1:{s:10:"subscriber";b:1;}', serialize($a));
//        dd();
    }

    public function render()
    {
        return view('livewire.testing');
    }
}

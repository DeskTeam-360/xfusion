<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use KeapGeek\Keap\Facades\Keap;

class ContactController extends Controller
{
    public function see_contacts($contact_id)
    {
        $data = Keap::contact()->find($contact_id, [
            'job_title', 'custom_fields'
        ]);
        dd($data);
    }

    public function tag_list()
    {
        $data = Keap::tag()->list();
        dd($data);
    }

    public function applyTags($contact_id, $tag_id)
    {
        $contact_ids = [];
        $contact_ids[] = $contact_id;
//        dd($contact_ids);
        Keap::tag()->applyToContacts(
            $tag_id,
            $contact_ids );

        $this->see_contacts($contact_id);
    }
}

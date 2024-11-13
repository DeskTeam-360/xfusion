<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use function PHPUnit\Framework\isEmpty;

class CampaignController extends Controller
{
    public function index()
    {
        return view(
            'admin.campaign.index'
        );
    }

    public function create_company()
    {
        return view(
            'admin.campaign.company.create'
        );
    }

    public function create()
    {
        return view('admin.campaign.create');
    }

    public function edit(string $id)
    {
        return view('admin.campaign.edit', compact('id'));
    }

    public function create_independent_user($id)
    {
        return view(
            'admin.campaign.independent-user.create', compact('id')
        );
    }

    public function listTag($id)
    {
        $keapTag = [];
        $keapTagApply = [];
        $keaps = User::find($id)->meta->where('meta_key', '=', 'keap_contact_id')->first();

        if ($keaps == null || $keaps == []) {
            return redirect()->back();
        } else {
            $keapTag = User::find($id)->meta->where('meta_key', '=', 'keap_tags')->first();
            $keapTagApply = User::find($id)->meta->where('meta_key', '=', 'keap_tags_applies')->first();
            if ($keapTag == null || $keapTag == []) {
                $keapTag = [];
                $keapTagApply = [];
            } else {
                $keapTag = explode(';',$keapTag->meta_value);
                $keapTagApply = explode(';',$keapTagApply->meta_value);
            }
        }

        return view(
            'admin.campaign.independent-user.index', compact('id', 'keapTag','keapTagApply')
        );
    }
}

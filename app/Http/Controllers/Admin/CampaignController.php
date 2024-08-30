<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

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
}

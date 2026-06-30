<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CompanyGroupController extends Controller
{
    public function index()
    {
        return view('admin.company-group.index');
    }

    public function create()
    {
        return view('admin.company-group.create');
    }

    public function edit(string $id)
    {
        return view('admin.company-group.edit', compact('id'));
    }
}

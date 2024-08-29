<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LimitLinkController extends Controller
{
    public function index()
    {
        return view('admin.limitlink.index');
    }

    public function create()
    {
        return view('admin.limitlink.create');
    }
    public function edit($id)
    {
        return view('admin.limitlink.edit',compact('id'));
    }

}

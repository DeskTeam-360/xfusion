<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class OneOnOneController extends Controller
{
    public function index()
    {
        return view('admin.one-on-one.index');
    }

    public function create()
    {
        return view('admin.one-on-one.create');
    }

    public function edit(string $id)
    {
        return view('admin.one-on-one.edit', compact('id'));
    }
}

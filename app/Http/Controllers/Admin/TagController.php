<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TagController extends Controller
{

    public function index()
    {
        return view('admin.tag.index');
    }
    public function create()
    {
        return view('admin.tag.create');
    }
    public function show($id)
    {
        return view('admin.tag.show',compact('id'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CourseGroupController extends Controller
{
    public function index()
    {
        return view('admin.course-list-group.index');
    }

    public function create()
    {
        return view('admin.course-list-group.create');
    }
    public function edit($id)
    {
        return view('admin.course-list-group.edit',compact('id'));
    }
    public function show($id)
    {
        return view('admin.course-list-group.show',compact('id'));
    }

}

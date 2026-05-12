<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CourseScoringGroupController extends Controller
{
    public function index()
    {
        return view('admin.course-scoring-group.index');
    }

    public function create()
    {
        return view('admin.course-scoring-group.create');
    }

    public function edit(string $id)
    {
        return view('admin.course-scoring-group.edit', compact('id'));
    }
}

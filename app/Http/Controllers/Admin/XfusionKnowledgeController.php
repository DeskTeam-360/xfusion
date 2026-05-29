<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class XfusionKnowledgeController extends Controller
{
    public function index()
    {
        return view('admin.xfusion-knowledge.index');
    }

    public function create()
    {
        return view('admin.xfusion-knowledge.create');
    }

    public function edit(string $xfusion_knowledge)
    {
        return view('admin.xfusion-knowledge.edit', ['id' => $xfusion_knowledge]);
    }
}

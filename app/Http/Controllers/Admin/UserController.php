<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use KeapGeek\Keap\Facades\Keap;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view(
            'admin.user.index'
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.user.create');
    }


    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return view('admin.user.reset-password',compact('id'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return view('admin.user.edit', compact('id'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Send Keap mail to user
     */
    public function keapMailSend($contactId)
    {
        try {
            Keap::contact()->tag($contactId, [1942]);
            return redirect()->back()->with('swal', [
                'title' => 'Keap mail sent successfully',
                'icon' => 'success',
                'timeout' => 3000
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('swal', [
                'title' => 'Failed to send Keap mail: ' . $e->getMessage(),
                'icon' => 'error',
                'timeout' => 5000
            ]);
        }
    }
}

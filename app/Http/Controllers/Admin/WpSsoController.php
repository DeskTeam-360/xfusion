<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\WpSso;
use Illuminate\Http\Request;

class WpSsoController extends Controller
{
    public function __invoke(Request $request)
    {
        $path = (string) $request->query('to', '/');
        if (! WpSso::isSafePath($path)) {
            abort(422, 'Invalid destination.');
        }

        return redirect()->away(WpSso::destination((int) $request->user()->ID, $path));
    }
}

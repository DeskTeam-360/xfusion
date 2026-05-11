<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Redirect setelah login: Company Admin menuju portal /company/dashboard.
     */
    protected function authenticated(\Illuminate\Http\Request $request, $user)
    {
        if (\App\Support\CompanyAdmin::isCompanyAdminPortalUser($user)
            && \App\Support\CompanyAdmin::portalCompanyMetaId($user)) {
            return redirect()->route('company.portal.dashboard');
        }
    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}

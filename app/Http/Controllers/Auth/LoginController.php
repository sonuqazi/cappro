<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Artisan;
use App\Models\Ldap;
use App\Services\LdapService;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;

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
     * Where to redirect users after login.
     *
     * @var string
     */
    protected function credentials(Request $request)
    {
        if($request->email_verification_token){
            $user = User::where('user_name', $request->username)->get()->first();
            $user->update([        
                     'status' => 1,
                     'email_verification_token' => '',
                     'password' => Hash::make($request->password),                //    ]);
            ]);
        }
        session(['password' => $request->get('password')]);
        session(['uid' => $request->get('username')]);
        return [
            'user_name' => $request->get('username'),
            'password' => $request->get('password'),
            'is_deleted' => 0,
        ];
    }

    public function username()
    {
        return 'username';
    }
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');

        $this->ldapObj = new ldapService(); // Ldap Service object

        // Update env file to configer LDAP details
        $ldapSettings = $this->ldapObj->getLdapSetting();// Get LDAP Settings details from storage
        if($ldapSettings){
            $path = app()->environmentFilePath();// .env file
            if (file_exists($path)) {
                // Update LDAP env variables
                file_put_contents($path, str_replace(                    
                    'LDAP_CONNECTION' . '=' . env('LDAP_CONNECTION'), 'LDAP_CONNECTION' . '=' . $ldapSettings->default_domain, file_get_contents($path)
                ));
                file_put_contents($path, str_replace(                    
                    'LDAP_HOST' . '=' . env('LDAP_HOST'), 'LDAP_HOST' . '=' . $ldapSettings->dns_server, file_get_contents($path)
                ));
                file_put_contents($path, str_replace(
                    'LDAP_PORT' . '=' . env('LDAP_PORT'), 'LDAP_PORT' . '=' . $ldapSettings->ldap_server, file_get_contents($path)
                ));
                file_put_contents($path, str_replace(
                    'LDAP_USERNAME' . '=' . env('LDAP_USERNAME'), 'LDAP_USERNAME' . '=' . $ldapSettings->search_user, file_get_contents($path)
                ));
                file_put_contents($path, str_replace(
                    'LDAP_PASSWORD' . '=' . env('LDAP_PASSWORD'), 'LDAP_PASSWORD' . '=' . $ldapSettings->password, file_get_contents($path)
                ));
                file_put_contents($path, str_replace(
                    'LDAP_BASE_DN' . '=' . env('LDAP_BASE_DN'), 'LDAP_BASE_DN' . '=' . $ldapSettings->search_base, file_get_contents($path)
                ));
                // Reload the cached config       
                if (file_exists(app()->getCachedConfigPath())) {
                    Artisan::call("config:cache");
                    Artisan::call("config:clear");
                }
            }
        }        
    }

}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateLdapRequest;
use App\Models\Ldap;
use App\Services\LdapService;

class LdapController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->ldapObj = new ldapService(); // Ldap Service object
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ldapSettings = $this->ldapObj->getLdapSetting();
        if($ldapSettings){
            return view('ldap.edit', compact('ldapSettings'));
        }else{
            return view('ldap.index');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();
        //dd($data);
        $result = $this->ldapObj->saveLdapSetting($data);
        return redirect()->back()->with('success','LDAP setting added successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Ldap  $ldap
     * @return \Illuminate\Http\Response
     */
    public function show(Ldap $ldap)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request  $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request  $request, $id)
    {
        $ciphering = env('CIPHERING');
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options   = env('OPTION');
        $encryption_iv = env('ENCRYPTION_IV');
        $encryption_key = env('ENCRYPTION_KEY');
        $encryption = openssl_encrypt($request->password, $ciphering, $encryption_key, $options, $encryption_iv);
        $request['password'] = $encryption;
        $result = $this->ldapObj->editLdapSettings($request, $id);
        if($result){
            return redirect()->back()->with('success','LDAP settings edited successfully.');
        }else{
            return redirect()->back()->with('error','Unable to update LDAP settings.');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateLdapRequest  $request
     * @param  \App\Models\Ldap  $ldap
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateLdapRequest $request, Ldap $ldap)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Ldap  $ldap
     * @return \Illuminate\Http\Response
     */
    public function destroy(Ldap $ldap)
    {
        //
    }
}

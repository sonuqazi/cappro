<?php
namespace App\Services;

use App\Models\Ldap;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\UniversityUser;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
class LdapService
{
    /** 
     * Save ldap setting in storage
     * @param object $request
     * @return boolean
     */
    public function saveLdapSetting($request){
        $user=Auth::user();
        
        $data['default_domain'] = $request['default_domain'];
        $data['dns_server'] = $request['dns_server'];
        $data['ldap_server'] = $request['ldap_server'];
        $data['is_use_tls'] = isset($request['is_use_tls']) ? 1 : 0;        
        $data['search_user'] = $request['search_user'];
        $ciphering = env('CIPHERING');
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options   = env('OPTION');
        $encryption_iv = env('ENCRYPTION_IV');
        $encryption_key = env('ENCRYPTION_KEY');
        $encryption = openssl_encrypt($request['password'], $ciphering, $encryption_key, $options, $encryption_iv);
        $data['password'] = $encryption;
        $data['search_base'] = $request['search_base'];
        $data['ldap_schema'] = $request['ldap_schema'];
        $data['is_staft_authentication'] = isset($request['is_staft_authentication']) ? 1 : 0;
        $data['is_client_authentication'] = isset($request['is_client_authentication']) ? 1 : 0;
        $data['created_by'] = $user->university_users->id;
        $data['updated_by'] = $user->university_users->id;
        $data['created_at'] = Carbon::now();
        $data['updated_at'] = Carbon::now();
        
        $result = Ldap::insert($data);
        return $result;
	}

    /** 
     * Get ldap setting from storage
     * @param 
     * @return $result
     */
    public function getLdapSetting(){
        $result = Ldap::select('ldaps.*')->get()->first();
        return $result;
    }

    /** 
     * Edit ldap setting in storage
     * @param \Illuminate\Http\Request  $request
     * @param int $id
     * @return boolean
     */
    public function editLdapSettings($request, $id)
    {
        $user=Auth::user();
        $pwd = Ldap::select('ldaps.password')->where('id', $id)->get()->first();
        
        if(empty($request['password'])){
            $password = $pwd->password;
        }else{
            $password = $request['password'];
        }
        
        $data['default_domain'] = $request['default_domain'];
        $data['dns_server'] = $request['dns_server'];
        $data['ldap_server'] = $request['ldap_server'];
        $data['is_use_tls'] = isset($request['is_use_tls']) ? 1 : 0;        
        $data['search_user'] = $request['search_user'];
        $data['password'] = $password;
        $data['search_base'] = $request['search_base'];
        $data['ldap_schema'] = $request['ldap_schema'];
        $data['is_staft_authentication'] = isset($request['is_staft_authentication']) ? 1 : 0;
        $data['is_client_authentication'] = isset($request['is_client_authentication']) ? 1 : 0;
        $data['updated_by'] = $user->university_users->id;
        $data['updated_at'] = Carbon::now();
        $result = Ldap::where('id', $id)->update($data);
        return $result;
    }
}
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserProfile;
use App\Models\User;
use App\Models\Notification;
use App\Models\UserNotification;
use App\Models\Organization;
use Spatie\Permission\Models\Role;
use App\Mail\UserRegistered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Services\UserService;
use App\Services\UserProfileService;
use App\Services\EmailTemplateService;
use Illuminate\Support\Facades\Config;
use DB;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\URL;

class UserController extends Controller {

	/**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
        $this->userObj = new UserService(); // user profile Service object
		$this->userProfileObj = new UserProfileService(); // user profile Service object
		$this->emailTemplateObj = new EmailTemplateService(); // email template Service object 
        //$this->uploadFileObj = new UploadFilesService(); // user profile Service object
    }

	/**
	* 
	* Custom Register Client Function
	* @param  \Illuminate\Http\Request  $request
	* @return Json
	*/
	public function client_register(Request $request)
	{
		$data = $request->all();
		$validator = Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'user_name' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
			'title' => ['required', 'string', 'max:255'],
			'phone' => ['required'],
            'department' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
		]);
        if ($validator->fails())
			return response()->json(['status' => 400, 'response' => 'error', 'msg' => $validator->errors()->first()]);
	    $user = User::create([
			// 'title' => $data['title'],
			'first_name' => $data['first_name'],
			'last_name' => $data['last_name'],
			'user_name' => strtolower($data['user_name']),
			'email' => $data['email'],
			'password' => Hash::make($data['password']),
			'email_verification_token' => Str::random(32),
			'role_id' => $data['role_id'],
			'created_by' => 5,
			//'department'=> $data['department'],
			'status'=> 0
		]);
		//Create as university user
		$univerUser = $this->userObj->saveUniversityUser($user->id, date('Y-m-d H:i:s'), date('Y-m-d H:i:s'));
		$user_profile = UserProfile::create([
			 'user_id' => $user->id,
			 'title' =>  $data['title'],
			 'department'=> $data['department'],
			 'phone' => $data['phone']    
		]);	
		$role = Role::where('id', $data['role_id'])->first();
		$user->assignRole($role['name']);
	    // insert only if user is client
	    if ( $data['role_id'] == 2 ) {
	    	$notifications = Notification::where('available_for', 'client')->get();            
	    	$user_noti = array();
	    	foreach ($notifications as $key => $value) {
	    		$user_noti['user_id'] = $user['id'];
	    		$user_noti['notification_id'] = $value['id'];
	    		$user_noti['status'] = 1;
	    		$user_noti['created_at'] = date('Y-m-d H:i:s');
	    		$user_noti['updated_at'] = date('Y-m-d H:i:s');
	    		UserNotification::create($user_noti);
	    	}	    	
	    }		
		$email = $user->email; 
		Mail::to($email)->send(new UserRegistered($user));
		return response()->json(['status' => 201, 'response' => 'success','id' => $user->id,'msg'=>'You are successfully registered to Capstone Pro. Please check you email to activate your account.']);
	}

    /**
	* Update Client Details
	* @param  \Illuminate\Http\Request  $request
	* @return Json
	*/

	public function update_client_details(Request $request) {
		$validator = Validator::make($request->all(), [
			'user_id' => ['required'],
			'organization_name' => ['required'],
			'organization_description' => ['required', 'string'],
			'address' => ['required', 'string', 'max:255'],
			'country' => ['required', 'string', 'max:255'],
			'city' => ['required', 'string', 'max:255'],
			'state' => ['required', 'string', 'max:255'],
			'organization_type' => ['required', 'string', 'max:255'],
			'postal_code' => ['required', 'string', 'max:255'],		
		]);

		if ($validator->fails())
            return response()->json(['status' => 400, 'response' => 'error', 'msg' => $validator->errors()->first()]);
         
        if(empty($request['org_id'])) {
         $org =  Organization::create(['name' => $request['organization_name']]);
         $request['org_id'] = $org->id;
        }   


		$profile =  UserProfile::where('user_id',$request['user_id'])->update([
			// 'user_id' => $request['user_id'],
			'org_id' => $request['org_id'],
			'organization_description' => $request['organization_description'],
			'address' => $request['address'],
			'address2' => $request['address2'],
			'city' => $request['city'],
			'country' => $request['country'],
			'state' => $request['state'],
			'website'=> $request['website'],
			'organization_type'=> $request['organization_type'],
			'postal_code'=> $request['postal_code'],
			'no_of_employess'=> $request['no_of_employess'],
			'industry'=> $request['industry'],
			'academic_field'=> $request['academic_field'],
			'updated_at'=> date('Y-m-d H:i:s'),
		]);


        Auth::loginUsingId($request['user_id']);

		return response()->json(['status' => 200, 'response' => 'success','url' => url('/home')]);
	}

    /**
	* Activate User Account
	* @param string $token
	* @return \Illuminate\Http\Response
	*/
	public function activate_account($token = null){
        
        if($token == null) {

    		return redirect()->route('login')->with('error','Invalid Login attempt!');

    	}

       $user = User::where('email_verification_token',$token)->first();

       if($user == null ){

        return redirect()->route('login')->with('error','Invalid Login attempt!');

       }

       $user->update([        
        'status' => 1,
        'email_verification_token' => ''

       ]);
       
        return redirect()->route('login')->with('success','Your account is activated, you can log in now!');

	}

	/**
	* Create new password
	* @param string $token
	* @return \Illuminate\Http\Response
	*/
	public function createPassword($token = null){
        //dd($id);
        if($token == null) {

    		return redirect()->route('login')->with('error','Invalid attempt!');

    	}

       $user = User::where('email_verification_token',$token)->first();
		//dd($user);
       if($user == null ){

        return redirect()->route('login')->with('error','Invalid attempt!');

       }

    //    $user->update([        
    //     'status' => 1,
    //     'email_verification_token' => ''

    //    ]);
       
	   return view('createPassword', compact('user'));

	}

	/**
	* 
	* Get Existing Organisation Names
	* @param  \Illuminate\Http\Request  $request
	* @return \Illuminate\Http\Response
	*/

	public function get_orgname(Request $request){
		$check = Organization::where('name', 'LIKE', $request->q.'%')->get();
	    return $check;
	}

	/**
	* 
	* Resend Activation Email
	* @return Json
	*/

	public function resend_activate_mail(){

		$user =  Auth::user();
		Mail::to($user->email)->send(new UserRegistered($user));
		if (Mail::failures()) {
			return ['status' => 400, 'response' => 'error', 'msg' => 'Something went wrong.Please try again.'];
		}else{
			return response()->json(['status' => 200, 'response' => 'success', 'msg' => 'Mail sent successfully']);
		}

	}
		
	/**
	 * Update profile is usr not fill 2nd step in registration
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */

	public function update_profile_details(Request $request) {
		if(empty($request['org_id'])) {
        	$org =  Organization::create(['name' => $request['organization_name'], 'updated_at'=> date('Y-m-d H:i:s'), 'created_at'=> date('Y-m-d H:i:s')]);
        	$request['org_id'] = $org->id;
        }
		// $profile =  UserProfile::create([
		// 	'user_id' => $request['user_id'],
		// 	'org_id' => $request['org_id'],
		// 	'organization_description' => $request['organization_description'],
		// 	'address' => $request['address'],
		// 	'address2' => $request['address2'],
		// 	'city' => $request['city'],
		// 	'country' => $request['country'],
		// 	'state' => $request['state'],
		// 	'website'=> $request['website'],
		// 	'organization_type'=> $request['organization_type'],
		// 	'postal_code'=> $request['postal_code'],
		// 	'no_of_employess'=> $request['no_of_employess'],
		// 	'industry'=> $request['industry'],
		// 	'academic_field'=> $request['academic_field'],
		// 	'updated_at'=> date('Y-m-d H:i:s'),
		// 	'created_at'=> date('Y-m-d H:i:s'),
		// ]);
		$profile = UserProfile::where('user_id',$request['user_id'])->update([
			// 'user_id' => $request['user_id'],
			'org_id' => $request['org_id'],
			'organization_description' => $request['organization_description'],
			'address' => $request['address'],
			'address2' => $request['address2'],
			'city' => $request['city'],
			'country' => $request['country'],
			'state' => $request['state'],
			'website'=> $request['website'],
			'organization_type'=> $request['organization_type'],
			'postal_code'=> $request['postal_code'],
			'no_of_employess'=> $request['no_of_employess'],
			'industry'=> $request['industry'],
			'academic_field'=> $request['academic_field'],
			'updated_at'=> date('Y-m-d H:i:s'),
		]);

		return redirect()->route('home')->with('success','Your account is activated successfully.');
	}

	/**
	* 
	* Upload User Profile Image
	*
	* @param  \Illuminate\Http\Request  $request
	* @return Json
	*/

	public function upload_user_avatar(Request $request){

		$validator = Validator::make($request->all(), ['avatar' => 'required|mimes:jpeg,png,jpg,gif,svg,webp',]);
		
		if ($validator->fails())
		{
			$response =  ['msg' => $validator->errors()->first(), 'status' => 400];
		}
		
		$image = $request->file('avatar');
		$avatar = $request->img_name . time() . '.' . $image->getClientOriginalExtension();
		$destinationPath = base_path('/public/avatar');
		$image->move($destinationPath, $avatar);
		$user =  Auth::user();
		$profile =  User::where('id',$user->id)->update(['profile_image' => $avatar ]);
		$response = ['status' => '200', 'response' => $avatar, 'full_url' => config('constants.avatar_url') . $avatar, 'msg' => 'success'];
		
		return response()->json($response);
	
	}

	/**
	* 
	* Update User Password
	*
	* @param  \Illuminate\Http\Request  $request
	* @return \Illuminate\Http\Response
	*/
	public function update_password(Request $request){
	 
		$rules = ['current_password' => 'required', 'password' => 'required|confirmed'];
        $msg['current_password.required'] = 'Current password is required';
        $msg['password.required'] = 'Password is required';
        $msg['password.confirmed'] = 'Confirm password and password doesnot match';

        $validator = Validator::make($request->all(), $rules, $msg);

        if ($validator->fails())
        {
            return redirect()->back()->with('error', $validator->errors()->first());
        }
        if (!(Hash::check($request->current_password, Auth::user()->password)))
        {
			session()->put('error', 'Incorrect current password');
            return redirect()->back();
        }

        $id = Auth::user()->id;
        $user = new User();
        $user->update_password($request->password, $id, $request->updated_at);
		session()->put('success','Password updated successfully.');
		return redirect()->back();
		
	}

	/**
	 * Validate User Name (Unique)
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return Json
	 */
	public function unique_user_name(Request $request) {
		try {
			$routeName = url()->previous();
			$strArray = explode('/',$routeName);
			$lastElement = end($strArray);
			//dd($lastElement);
			$checkUserName = User::where('user_name', $request['user_name'])->first();
			if ( !empty($checkUserName) ) {
				if($lastElement == 'home' || $lastElement == 'home?tab=profile'){
					if(Auth::user()->id == $checkUserName->id){
						return response()->json(true);
					}else{
						return response()->json(false);	
					}
				}else{
					if($request['user_name'] == $request['old_user_name']){
						return response()->json(true);
					}else{
						return response()->json(false);
					}
				}
	    	} else {
	    		return response()->json(true);
	    	}
    	} catch (Throwable $e) {
            return response()->json(['status' => 400, 'response' => 'error', 'msg' => $e]);
    	}
	}

	/**
	 * Validate User Email (Unique)
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return Json
	 */
	public function unique_email(Request $request) {
		try {
			$routeName = url()->previous();
			$strArray = explode('/',$routeName);
			$lastElement = end($strArray);
			//dd($request);
			$checkEmail = User::where('email', $request['email'])->first();
	    	if ( !empty($checkEmail) ) {
				if($lastElement == 'home' || $lastElement == 'home?tab=profile'){
					if(Auth::user()->id == $checkEmail->id){
						return response()->json(true);
					}else{
						return response()->json(false);	
					}
				}else{
					if($request['email'] == $request['old_email']){
						return response()->json(true);
					}else{
						return response()->json(false);
					}
				}
	    	} else {
	    		return response()->json(true);
	    	}
    	} catch (Throwable $e) {
            return response()->json(['status' => 400, 'response' => 'error', 'msg' => $e]);
    	}
	}

	/**
	 * Get archieved users from storage
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function archievedUsers(Request $request)
	{
		//DB::enableQueryLog();
		// dd(Auth::user()->role_id);
		$user_id = auth()->user()->id;
        $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
		if(Auth::user()->role_id==Config::get('constants.roleTypes.faculty'))
        { 
			$archievedUsers = User::Join('roles', 'roles.id', '=', 'users.role_id')
			->select('users.id', 'users.user_name', 'users.first_name', 'users.last_name', 'users.email', 'users.role_id', 'roles.name')
			->where('is_deleted', 1)
			->where('users.role_id', Config::get('constants.roleTypes.student'))->get();
        }else{
			$archievedUsers = User::Join('roles', 'roles.id', '=', 'users.role_id')
		->select('users.id', 'users.user_name', 'users.first_name', 'users.last_name', 'users.email', 'users.role_id', 'roles.name')
		->where('is_deleted', 1)->get();
		}
		$emailTemplateList = $this->emailTemplateObj->getAllActiveEmailTemplate();
		//dd(DB::getQueryLog());
		//dd($archievedUsers);
		return view('users.archievedUsers', compact('archievedUsers', 'user_profile_data', 'emailTemplateList'));
	}

	/**
     * get all archeved users from storage
     * @param \Illuminate\Http\Request  $request
     * @return json
     */
    public function allArchivedUsers(Request $request)
    {

        $user_id = auth()->user()->id;
        if ($request->ajax() && !empty($user_id)) {
            $return['recordsFiltered'] = $return['recordsTotal'] = $this->userObj->searchAllArchivedUsersCounts($request);
            $return['draw'] = $request->draw;
            $view_users = $this->userObj->allArchivedUsers($request);
            //dd($view_users);
            foreach ($view_users as $key => $user) {
				$data[$key]['user_name'] = '<span class="edit-archived-username-' . $user['id'] . '">' . $user['user_name'] . '</span>';
                $data[$key]['first_name'] = '<span class="edit-archived-first-name-' . $user['id'] . '">' . $user['first_name'] . '</span>';
				$data[$key]['last_name'] = '<span class="edit-archived-last-name-' . $user['id'] . '">' . $user['last_name'] . '</span>';
                $data[$key]['email'] = '<a class="edit-archived-email-' . $user['id'] . ' del-active openEmailTemplate" data-client-email="'.$user['email'].'" data-text="You are sending email to '.$user['first_name'].' '.$user['last_name'].' at '.$user['email'].'">' . $user['email'] . '</a>';
                $data[$key]['role'] = '<span class="edit-archived-org-' . $user['id'] . '">' . $user['role'] . '</span>';
                $data[$key]['edit'] = '<a class="edit-archived-user del-active" active-id = "' . $user['id'] . '" onclick="activate_user(' . $user['id'] . ')">Activate</a>';
                $data[$key]['id'] = $user['id'];
            }
			//dd($data);
            $return['data'] = !empty($data) ? $data : array();
            return response()->json($return);
        }
    }

	/**
     * Get spesific user from storage
     *
     * @param  int $student
     * @return Json
     */
	public function activateUser($student = null, $dateTime = null)
    {
		//Check role is active or not
		$roleIsActive = $this->userObj->roleIsActive($student);
		if($roleIsActive->roleIsActive){
			$result = $this->userObj->activateUser($student, $dateTime);
		}else{
			$result = 0;
		}
		return response()->json($result);
    }

	/**
     * Delete user profile image
     * @param \Illuminate\Http\Request  $request
     * @return json
     */
	public function deleteImage(Request $request)
	{
		$profile =  User::where('id',$request->id)->update(['profile_image' => '' ]);
		return response()->json(['status' => '200', 'response' => 'success', 'full_url' => base_path('/img/default-avtar.png')]);
	}
}

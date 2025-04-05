<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use App\Services\ProjectService;
use App\Services\UserProfileService;
use App\Services\UserService;
use App\Services\EmailTemplateService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdminUser;
use App\Mail\CreatePassword;
use App\Models\UserProfile;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\Notification;
use App\Models\UserNotification;
use App\Models\Organization;
use App\Mail\UserRegistered;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Validator;
use App\Traits\RoleTrait;

class AdminUsersController extends Controller
{

    use RoleTrait;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage Users', ['only' => ['index','store']]);
        $this->middleware('permission:Manage Clients', ['only' => ['index','store','listClients','search_all_clients','add_update_client']]);
        $this->projectObj = new ProjectService();
        $this->userProfileObj = new UserProfileService(); // user profile Service object
        $this->userObj = new UserService(); // user profile Service object
        $this->emailTemplateObj = new EmailTemplateService(); // email template Service object

        //$this->uploadFileObj = new UploadFilesService(); // user profile Service object
    }

    /**
     * List of all users
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        try {
            $user_id = auth()->user()->id;
            $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
            $roles = $this->getRoles();
            return view('users.index', ['user_profile_data' => $user_profile_data, 'roles' => $roles]);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('projects')->with('error', $e);
        }
    }

    /**
     * Add new user
     */
    public function store(Request $request)
    {
        try {
            $user_id = auth()->user()->id;
            $user_profile_data = $this->userProfileObj->getUserProfile($user_id);

            $validator = Validator::make($request->all(), [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'user_name' => ['required', 'string', 'max:255', 'unique:users'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            ]);

            if ($validator->fails())
                return redirect()->route('viewUsers')->withErrors($validator->errors()->first())->withInput();

            $pass = Str::random(8);
            $data = array(
                'first_name' => $request['first_name'],
                'last_name' => $request['last_name'],
                'user_name' => strtolower($request['user_name']),
                'email' => $request['email'],
                'role_id' => $request['role_id'],
                'status' => 1,
                'email_verification_token' => Str::random(32),
                'created_by' => Auth::user()->university_users->id,
                'created_at' => $request['created_at'],
                'updated_at' => $request['updated_at'],
                'password' => $request['role_id'] == 2 ? Hash::make($pass) : '$2y$10$8.HsIj8ZJ/H5u87neWsZ4O0aCXa2EMdLcghTceMNbMheYN75kJGn6'
            );
            $user = $this->userObj->saveUser($data);
            $role = Role::where('id', $request['role_id'])->first();
            $user->assignRole($role['name']);
            if ($request['role_id'] != 2) {
                $un_user = $this->userObj->saveUniversityUser($user->id, $request['created_at'], $request['updated_at']);
                $email = $request['email'];
                $user->send_password = 1;
                $user->password = $pass;
                Mail::to($email)->send(new CreatePassword($user));
            } else {
                $email = $request['email'];
                $user->send_password = 1;
                $user->password = $pass;
                Mail::to($email)->send(new CreatePassword($user));
            }
            switch ($request['role_id']) {
                case 1:  $avl='admin'; break;
                case 3:  $avl='faculty'; break;
                case 4:  $avl='student'; break;
                case 5:  $avl='ta'; break;
            }

            $notifications = Notification::where('available_for', $avl)->get();
            $user_noti = array();
            foreach ($notifications as $key => $value) {
                $user_noti['user_id'] = $user->id;
                $user_noti['notification_id'] = $value['id'];
                $user_noti['status'] = 1;
                $user_noti['created_at'] = $request['created_at'];
                $user_noti['updated_at'] = $request['updated_at'];
                UserNotification::create($user_noti);
            }

            return redirect()->route('viewUsers')->with('success', 'User added successfully');
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('viewUsers')->with('error', $e);
        }
    }

    /**
     * List all clients
     * @param 
     * @return array 
     */
    public function listClients($course_id = null, $semId = null)
    {
        $user_id = auth()->user()->id;
        $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
        $roles = $this->getRoles();
        $emailTemplateList = $this->emailTemplateObj->getAllActiveEmailTemplate();
        return view('users.clients', compact('user_profile_data', 'user_id', 'course_id', 'roles', 'semId', 'emailTemplateList'));
    }


    /**
     * Search clients
     * @param
     * @return json
     */
    public function search_all_clients(Request $request)
    {
       

        $user_id = auth()->user()->id;
        if ($request->ajax() && !empty($user_id)) {
          
            $return['recordsFiltered'] = $return['recordsTotal'] = $this->projectObj->search_all_client_counts($request);
            $return['draw'] = $request->draw;
            $view_users = $this->userObj->allClients($request);
            // dd($view_users);
            foreach ($view_users as $key => $user) {
                // if (!empty($user['user_profiles']['org_id'])) {
                //     $org_details = Organization::find($user['user_profiles']['org_id']);
                //     $org_name = isset($org_details['name']) ? $org_details['name'] : '';
                // } else {
                //     $org_name = '';
                //     $org_id = '';
                // }
                $address = !empty($user['user_profiles']['address']) ? $user['user_profiles']['address'] : '';
                $address2 = !empty($user['user_profiles']['address2']) ? $user['user_profiles']['address2'] : '';
                $phone = !empty($user['user_profiles']['phone']) ? $user['user_profiles']['phone'] : '';
                $city = !empty($user['user_profiles']['city']) ? $user['user_profiles']['city'] : '';
                $state = !empty($user['user_profiles']['state']) ? $user['user_profiles']['state'] : '';
                $country = !empty($user['user_profiles']['country']) ? $user['user_profiles']['country'] : '';
                $department = !empty($user['user_profiles']['department']) ? $user['user_profiles']['department'] : '';
                $title = !empty($user['user_profiles']['title']) ? $user['user_profiles']['title'] : '';
                $organization_type = !empty($user['user_profiles']['organization_type']) ? $user['user_profiles']['organization_type'] : '';
                $no_of_employess = !empty($user['user_profiles']['no_of_employess']) ? $user['user_profiles']['no_of_employess'] : '';
                $industry = !empty($user['user_profiles']['industry']) ? $user['user_profiles']['industry'] : '';
                $academic_field = !empty($user['user_profiles']['academic_field']) ? $user['user_profiles']['academic_field'] : '';
                $website = !empty($user['user_profiles']['website']) ? $user['user_profiles']['website'] : '';
                $organization_description = !empty($user['user_profiles']['organization_description']) ? $user['user_profiles']['organization_description'] : '';

                $postal_code = !empty($user['user_profiles']['postal_code']) ? $user['user_profiles']['postal_code'] : '';
                $data[$key]['user_name'] = '<span class="edit-student-username-' . $user['id'] . '">' . $user['user_name'] . '</span>';
                $data[$key]['first_name'] = '<span class="edit-student-first-name-' . $user['id'] . '">' . $user['first_name'] . '</span>';
                $data[$key]['last_name'] = '<span class="edit-student-last-name-' . $user['id'] . '">' . $user['last_name'] . '</span>';
                $data[$key]['email'] = '<a class="edit-student-email-' . $user['id'] . ' del-active openEmailTemplate" data-client-email="'.$user['email'].'" data-text="You are sending email to '.$user['first_name'].' '.$user['last_name'].' at '.$user['email'].'">' . $user['email'] . '</a>';
                $data[$key]['org'] = $user['org'] . '<span class="edit-student-org-' . $user['id'] . ' hidden">' . $user['org'] . '</span>';
                if(Auth::user()->role_id == 1){
                    $data[$key]['edit'] = '<a class="edit-client-user del-active" edit-id = "' . $user['id'] . '" >Edit</a><input type="hidden" id="address-' . $user['id'] . '" value="' . $address . '"><input type="hidden" id="address2-' . $user['id'] . '" value="' . $address2 . '"><input type="hidden" id="city-' . $user['id'] . '" value="' . $city . '"><input type="hidden" id="state-' . $user['id'] . '" value="' . $state . '"><input type="hidden" id="country-' . $user['id'] . '" value="' . $country . '"><input type="hidden" id="postal_code-' . $user['id'] . '" value="' . $postal_code . '"><input type="hidden" id="phone-' . $user['id'] . '" value="' . $phone . '"><input type="hidden" id="org-desc-' . $user['id'] . '" value="' . $organization_description . '"><input type="hidden" id="department-' . $user['id'] . '" value="' . $department . '"><input type="hidden" id="title-' . $user['id'] . '" value="' . $title . '"><input type="hidden" id="org-type-' . $user['id'] . '" value="' . $organization_type . '"><input type="hidden" id="industry-' . $user['id'] . '" value="' . $industry . '"><input type="hidden" id="academic-' . $user['id'] . '" value="' . $academic_field . '"> <input type="hidden" id="employees-' . $user['id'] . '" value="' . $no_of_employess . '"><input type="hidden" id="website-' . $user['id'] . '" value="' . $website . '">';
                }elseif($user['created_by'] == Auth::user()->university_users->id){
                    $data[$key]['edit'] = '<a class="edit-client-user del-active" edit-id = "' . $user['id'] . '" >Edit</a><input type="hidden" id="address-' . $user['id'] . '" value="' . $address . '"><input type="hidden" id="address2-' . $user['id'] . '" value="' . $address2 . '"><input type="hidden" id="city-' . $user['id'] . '" value="' . $city . '"><input type="hidden" id="state-' . $user['id'] . '" value="' . $state . '"><input type="hidden" id="country-' . $user['id'] . '" value="' . $country . '"><input type="hidden" id="postal_code-' . $user['id'] . '" value="' . $postal_code . '"><input type="hidden" id="phone-' . $user['id'] . '" value="' . $phone . '"><input type="hidden" id="org-desc-' . $user['id'] . '" value="' . $organization_description . '"><input type="hidden" id="department-' . $user['id'] . '" value="' . $department . '"><input type="hidden" id="title-' . $user['id'] . '" value="' . $title . '"><input type="hidden" id="org-type-' . $user['id'] . '" value="' . $organization_type . '"><input type="hidden" id="industry-' . $user['id'] . '" value="' . $industry . '"><input type="hidden" id="academic-' . $user['id'] . '" value="' . $academic_field . '"> <input type="hidden" id="employees-' . $user['id'] . '" value="' . $no_of_employess . '"><input type="hidden" id="website-' . $user['id'] . '" value="' . $website . '">';
                }else{
                    $data[$key]['edit'] = '';
                }
                $data[$key]['id'] = $user['id'];
            }

            $return['data'] = !empty($data) ? $data : array();
            return response()->json($return);
        }
    }

    /**
     *  Store client detils
     *  @param 
     *  @return /
     */
    public function add_update_client(Request $request)
    {
        //dd(Auth::user()->university_users->id);
        $user = [];
        $data = $request->all();
        $validator = Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'user_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            //'title' => ['required', 'string', 'max:255'],
            //'phone' => ['required'],
            // 'department' => ['required', 'string', 'max:255']
        ]);

        if ($validator->fails())
            return redirect()->route('viewClients')->withErrors($validator->errors()->first())->withInput();

        $pass = Str::random(8);
        $arr = array(
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'user_name' => $data['user_name'],
            'email' => $data['email'],
            'password' => Hash::make($pass),
            'email_verification_token' => Str::random(32),
            'role_id' => 2,
            'status' => 1,
            'created_by' => Auth::user()->university_users->id
        );
        if (empty($request['org_id'])) {
            $org =  Organization::create(['name' => $request['organization_name']]);
            $request['org_id'] = $org->id;
        }


        $arr2 = array(
            'title' =>  $data['title'] ? $data['title'] : 'N/A',
            'department' => $data['department'],
            'phone' => $data['phone'],
            'org_id' => $request['org_id'],
            'organization_description' => $request['organization_description'],
            'address' => $request['address'],
            'address2' => $request['address2'],
            'city' => $request['city'],
            'country' => $request['country'],
            'state' => $request['state'],
            'website' => $request['website'],
            'organization_type' => $request['organization_type'],
            'postal_code' => $request['postal_code'],
            'no_of_employess' => $request['no_of_employess'],
            'industry' => $request['industry'],
            'academic_field' => $request['academic_field'],
            'updated_at' => Carbon::now()
        );


        if (!empty($request->user_id)) {
            $verificationToken = $arr['email_verification_token'];
            $arr['updated_at'] = $request['updated_at'];
            $arr2['updated_at'] = $request['updated_at'];
            unset($arr['email'], $arr['password'], $arr['email_verification_token']);
            if($request['email'] != $request['old_email']){
                $arr['email'] = $request['email'];
                $arr['status'] = 0;
                $arr['email_verification_token'] = $verificationToken;
            }
            $edit_user = $this->userObj->edit_client($arr, $arr2, $request->user_id);
            if($request['email'] != $request['old_email']){
                $user['email_verification_token'] = $verificationToken;
                $user['first_name'] = $data['first_name'];
                $user['last_name'] = $data['last_name'];
                $user['role_id'] = $request['role_id'];

                $user = json_decode(json_encode($user), FALSE);
                Mail::to($request['email'])->send(new CreatePassword($user));
            }
            
            $msg = 'Client updated successfully';
        } else {
            $arr['created_at'] = $request['created_at'];
            $arr['updated_at'] = $request['updated_at'];
            $arr2['created_at'] = $request['created_at'];
            $arr2['updated_at'] = $request['updated_at'];
            $user = $this->userObj->add_client($arr, $arr2);
            $role = Role::where('id', $request['role_id'])->first();
            $user->assignRole($role['name']);
            $email = $user->email;
            $user->send_password = 1;
            $user->password = $pass;
            $notifications = Notification::where('available_for', 'client')->get();
            $user_noti = array();
            foreach ($notifications as $key => $value) {
                $user_noti['user_id'] = $user['id'];
                $user_noti['notification_id'] = $value['id'];
                $user_noti['status'] = 1;
                $user_noti['created_at'] = $request['created_at'];
                $user_noti['updated_at'] = $request['updated_at'];
                UserNotification::create($user_noti);
            }
            $msg = 'Client added successfully';
        }

        if (isset($user->send_password)) {
            Mail::to($email)->send(new CreatePassword($user));

            if (Mail::failures()) {
                return redirect()->route('viewClients')->with('error', 'Mail couldnot be send');
            }
        }

        return redirect()->route('viewClients')->with('success', $msg);
    }
}

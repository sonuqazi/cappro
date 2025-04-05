<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Organization;
use App\Models\Notification;
use App\Models\MasterSetting;
use App\Models\UserNotification;
use DB;
use App\Services\UserProfileService;
use App\Services\MessageService;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->userProfileObj = new UserProfileService(); // user profile Service object
        $this->messageObj = new MessageService();
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index() {
        try {
            $user_id = auth()->user()->id;
            $user =  Auth::user();
            if(!isset($_GET['tab'])){
                $update = $this->messageObj->allUnseenMessageCountUpdate();
            }
            $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
           // dd($user_profile_data);
            if ( !empty($user_profile_data->user_profiles->org_id) ) {
                $org_details = Organization::find($user_profile_data->user_profiles->org_id);
                $org_name = isset($org_details->name) ? $org_details->name : '';
            } else {
                $org_name = '';
            }
            $user_data = User::findOrFail($user_id);
            //  DB::enableQueryLog();
           
            switch ($user->role_id) {
                case 1:  $userType='admin'; break;
                case 2:  $userType='client'; break;
                case 3:  $userType='faculty'; break;
                case 4:  $userType='student'; break;
                case 5:  $userType='ta'; break;
            }

            $notifications = Notification::where('available_for', $userType)->get();
             
            $user_noti = array();
            foreach ($notifications as $key => $value) {
                $user_noti['user_id'] = $user->id;
                $user_noti['notification_id'] = $value['id'];
                $user_noti['status'] = 1;
                $user_noti['created_at'] = date('Y-m-d H:i:s');
                $user_noti['updated_at'] = date('Y-m-d H:i:s');
                $notiCount=UserNotification::where('notification_id', $value['id'])
                                             ->where('user_id', $user->id)->count();
                if($notiCount<1){
                    UserNotification::create($user_noti);
                }
                
            }
            $noti = UserNotification::with([ 'notification', 'user' ])
            ->select('user_notifications.id as unid','user_notifications.user_id', 'notifications.id as nid','user_notifications.status','notifications.name','notifications.available_for')
            ->join('notifications', 'notifications.id', 'user_notifications.notification_id')
            ->where('user_notifications.user_id', $user_id)
            ->where('notifications.available_for',$userType)->get();
            //  dd(DB::getQueryLog());
            // dd($noti);
            $role=Auth::user()->role_id;
            if($role==2)
            {
                if(isset($_GET['tab']) && $_GET['tab'] == 'profile'){
                    return view('home', ['user_profile_data' => $user_profile_data, 'user_data' => $user_data, 'org_name' => $org_name, 'notifications' => $noti]);
                }
                elseif(isset($_GET['tab']) && $_GET['tab'] == 'password'){
                    return view('home', ['user_profile_data' => $user_profile_data, 'user_data' => $user_data, 'org_name' => $org_name, 'notifications' => $noti]);
                }
                elseif(isset($_GET['tab']) && $_GET['tab'] == 'notifications'){
                    return view('home', ['user_profile_data' => $user_profile_data, 'user_data' => $user_data, 'org_name' => $org_name, 'notifications' => $noti]);
                }
                else{
                    return redirect()->route('allProject');
                }
            }else{
                return view('home', ['user_profile_data' => $user_profile_data, 'user_data' => $user_data, 'org_name' => $org_name, 'notifications' => $noti]);
            }
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('home')->with('error', $e);
        }
    }
}

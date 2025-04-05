<?php

namespace App\Services;
use DB;
use App\Models\User;
use App\Models\Team;
use App\Models\Task;
use App\Models\UserProfile;
use App\Models\UniversityUser;
use App\Models\Notification;
use App\Models\UserNotification;
use App\Models\ProjectCourse;
use App\Models\TeamStudent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use View;
class UserService
{

   /**
    * Save User Details.
    * @param Object $request
    * @return Object
    */
   public function saveUser($data)
   {
      $user =  User::create($data);
      return $user;
   }

   /**
    * Save University User Details.
    * @param Object $request
    * @return Object
    */
   public function saveUniversityUser($id, $createdAt, $updatedAt)
   {
      $user =  UniversityUser::create(['user_id' => $id, 'created_at' => $createdAt, 'updated_at' => $updatedAt]);
      return $user;
   }

   /**
    * List all clients.
    * @param Object $request
    * @return Object
    */
    
    public function allClients($request)
    {
        // DB::enableQueryLog(); 
        //dd($request->semId);
        $keywords = trim($request->search['value']);
        $start = $request->start;
        $limit = $request->length;
        $order_index = $request->order[0]['column'];
        $order_by = $request->columns[$order_index]['data'];
        $sort_by = $request->order[0]['dir'];
        if(!empty($request->semId)){
            $queries = User::with('user_profiles')->select('user_name', 'first_name', 'last_name', 'email', 'roles.name as role_id', 'users.id', 'users.role_id as role_assigned', 'organizations.name as org', 'organizations.id as org_id', 'users.created_by')
            ->where('users.role_id',  '=', '2')
            ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->join('organizations', 'organizations.id', '=', 'user_profiles.org_id')
            ->join('university_users', 'university_users.user_id', 'users.id')
            ->join('projects', 'projects.client_id', 'university_users.id')
            ->join('project_courses', 'project_courses.project_id', 'projects.id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('courses.semester_id', $request->semId)
            ->groupBy('university_users.id');
            $keywords = trim($keywords);
            if (!empty($keywords)) {
                $search_words =  explode(' ', $keywords);
                $firstword = $search_words[0];
                $lastword = @$search_words[1];

                if (empty($lastword)) {
                    $queries->Where(function ($query) use ($firstword) {
                        $query->where('users.first_name', 'like', '%' . $firstword . '%')
                            ->orWhere('users.last_name', 'like', '%' . $firstword . '%')
                            ->orWhere('users.email', 'like', '%' . $firstword . '%')
                            ->orWhere('users.user_name', 'like', '%' . $firstword . '%')
                            ->orWhere('roles.name', 'like', '%' . $firstword . '%')
                            ->orWhere('organizations.name', 'like', '%' . $firstword . '%');
                    });
                } else {
                    $queries->Where(function ($query) use ($firstword, $lastword) {
                        $query->where('users.first_name', 'like', '%' . $firstword . '%')
                            ->Where('users.last_name', 'like', '%' . $lastword . '%')
                            ->orWhere('users.last_name', 'like', '%' . $firstword . ' ' . $lastword . '%')
                            ->orWhere('organizations.name', 'like', '%' . $firstword . ' ' . $lastword . '%');
                    });
                }
            }
        }else{
            $queries = User::with('user_profiles')->select('user_name', 'first_name', 'last_name', 'email', 'roles.name as role_id', 'users.id', 'users.role_id as role_assigned', 'organizations.name as org', 'organizations.id as org_id', 'created_by')
                //->where('users.status', '1')
                ->where('users.role_id',  '=', '2')
                ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
                ->join('organizations', 'organizations.id', '=', 'user_profiles.org_id')
                ->join('roles', 'roles.id', '=', 'users.role_id');
            $keywords = trim($keywords);
            if (!empty($keywords)) {
                $search_words =  explode(' ', $keywords);
                $firstword = $search_words[0];
                $lastword = @$search_words[1];

                if (empty($lastword)) {
                    $queries->Where(function ($query) use ($firstword) {
                        $query->where('users.first_name', 'like', '%' . $firstword . '%')
                            ->orWhere('users.last_name', 'like', '%' . $firstword . '%')
                            ->orWhere('users.email', 'like', '%' . $firstword . '%')
                            ->orWhere('users.user_name', 'like', '%' . $firstword . '%')
                            ->orWhere('roles.name', 'like', '%' . $firstword . '%')
                            ->orWhere('organizations.name', 'like', '%' . $firstword . '%');
                    });
                } else {
                    $queries->Where(function ($query) use ($firstword, $lastword) {
                        $query->where('users.first_name', 'like', '%' . $firstword . '%')
                            ->Where('users.last_name', 'like', '%' . $lastword . '%')
                            ->orWhere('users.last_name', 'like', '%' . $firstword . ' ' . $lastword . '%')
                            ->orWhere('organizations.name', 'like', '%' . $firstword . ' ' . $lastword . '%');
                    });
                }
            }
            if (!empty($request->course_id)) {
                $queries->Where('users.role_id', '4');
            }
        }
        // return $queries = $queries->Where('users.role_id', '3')->orderBy($order_by, $sort_by)
        return   $queries = $queries->orderBy($order_by, $sort_by)
            ->limit($limit, $start)
            ->get()->toArray();
            // dd(DB::getQueryLog());
    }

    /**
     * Add Client
     * @param Object $request, $request2
     * @return Object
     */
   

     public function add_client($request,$request2){
        $user = User::create($request);
        $request2['user_id'] =  $user->id;
        $user_profile = UserProfile::create($request2);
        //create university user in university_users table
        $univerUser = $this->saveUniversityUser($user->id, $request['created_at'], $request['updated_at']);
        return $user;
     }

     /**
      * Edit Client
      * @param Object $request, $request2
      * @param Int $user_id
      * @return Object
      */
     
    public function edit_client($request,$request2,$user_id){
        $user = User::where('id',$user_id)->update($request);
        $user_profile = UserProfile::where('user_id',$user_id)->update($request2);
        //check if university user exist
        $isUniversityUserExist = $this->isUniversityUser($user_id);
        //if  not exist in university_users table create them
        if(!$isUniversityUserExist){
            $univerUser = $this->saveUniversityUser($user_id, $request['updated_at'], $request['updated_at']);
        }
        return $user;
     } 

     /**
      * Check user notification
      * @param String $notification
      * @return Object
      */     
      public function user_notification($notification){
        $user = Notification::where('name',$notification)->with('userNotification')->first();
        
        return $user;
     }

     /**
      * Check university user exist
      * @param Int $id
      * @return Object
      */     

      public function isUniversityUser($id){
        $user = UniversityUser::where('user_id',$id)->first();
        
        return $user;
     }

     /**
       * List all clients.
       * @param Object $request
       * @return Object
      */     
    public function allArchivedUsers($request)
    {
        $keywords = trim($request->search['value']);
        $start = $request->start;
        $limit = $request->length;
        $order_index = $request->order[0]['column'];
        $order_by = $request->columns[$order_index]['data'];
        $sort_by = $request->order[0]['dir'];
        $queries = User::Join('roles', 'roles.id', '=', 'users.role_id')
		->select('users.id', 'users.user_name', 'users.first_name', 'users.last_name', 'users.email', 'users.role_id', 'roles.name as role')
		->where('is_deleted', 1);

        if(Auth::user()->role_id==Config::get('constants.roleTypes.faculty'))
        { 
            $queries->where('users.role_id', Config::get('constants.roleTypes.student'));
        }
        $keywords = trim($keywords);
        if (!empty($keywords)) {
            $search_words =  explode(' ', $keywords);
            $firstword = $search_words[0];
            $lastword = @$search_words[1];

            if (empty($lastword)) {
                $queries->Where(function ($query) use ($firstword) {
                    $query->where('users.first_name', 'like', '%' . $firstword . '%')
                        ->orWhere('users.last_name', 'like', '%' . $firstword . '%')
                        ->orWhere('users.email', 'like', '%' . $firstword . '%')
                        ->orWhere('users.user_name', 'like', '%' . $firstword . '%')
                        ->orWhere('roles.name', 'like', '%' . $firstword . '%');
                });
            } else {
                $queries->Where(function ($query) use ($firstword, $lastword) {
                    $query->where('users.first_name', 'like', '%' . $firstword . '%')
                        ->Where('users.last_name', 'like', '%' . $lastword . '%')
                        ->orWhere('users.last_name', 'like', '%' . $firstword . ' ' . $lastword . '%');
                });
            }
        }
        if (!empty($request->course_id)) {
            $queries->Where('users.role_id', '4');
        }
        // return $queries = $queries->Where('users.role_id', '3')->orderBy($order_by, $sort_by)
        return $queries = $queries->orderBy($order_by, $sort_by)
            ->limit($limit, $start)
            ->get()->toArray();
    }

    /**
    * @description Function get count of filtered users
    * @param Object $request
    * @return Object
    */
    public function searchAllArchivedUsersCounts($request)
    {
        $keywords = $request->search['value'];
        $queries = User::Join('roles', 'roles.id', '=', 'users.role_id')
		->select('users.id', 'users.user_name', 'users.first_name', 'users.last_name', 'users.email', 'users.role_id', 'roles.name')
		->where('is_deleted', 1);

        $keywords = trim($keywords);
        if (!empty($keywords)) {
            $search_words =  explode(' ', $keywords);
            $firstword = $search_words[0];
            $lastword = @$search_words[1];

            if (empty($lastword)) {
                $queries->Where(function ($query) use ($firstword) {
                    $query->where('users.first_name', 'like', '%' . $firstword . '%')
                        ->orWhere('users.last_name', 'like', '%' . $firstword . '%')
                        ->orWhere('users.email', 'like', '%' . $firstword . '%')
                        ->orWhere('users.user_name', 'like', '%' . $firstword . '%')
                        ->orWhere('roles.name', 'like', '%' . $firstword . '%');
                });
            } else {
                $queries->Where(function ($query) use ($firstword, $lastword) {
                    $query->where('users.first_name', 'like', '%' . $firstword . '%')
                        ->Where('users.last_name', 'like', '%' . $lastword . '%')
                        ->orWhere('users.last_name', 'like', '%' . $firstword . ' ' . $lastword . '%');
                });
            }
        }
        return $queries->count();
    }


    /**
    * Activate User
    * @param Int $user
    * @return Object
    */
    public function activateUser($user, $dateTime)
    {
        $result = User::where('id', $user)->update(['is_deleted' => '0', 'updated_at' => $dateTime]);
        return $result;
    }

    /**
    * Discussion Mail Notification Setting
    * @param Int $project_course_id
    * @return Object
    */
    public function discussionMailNotificationSetting($project_course_id)
    {
        $datas=TeamStudent::with(['university_students.university_users'])
        ->join('teams','teams.id','team_students.team_id')
        ->join('project_courses','project_courses.id','teams.project_course_id')->where('teams.project_course_id', $project_course_id)->get();
        foreach ($datas as $data) 
        {   
            $user=$data->university_students->university_users;
             $userId=$user->id;
            if($userId!=null)
            {              
               $email=$user->email;
               $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                                    ->where('status', 1)                               
                                                    ->where('notification_id', 31)->first();
                if(isset($permissionCheck))
                {
                    if($permissionCheck->status==1)
                    {
                        $mailSubject="A Team Started a Discussion";
                        //$mailMsg="Dear ".ucfirst($user->first_name).", <br>A Discussion has been started by someone";
                        $content = "This is an automated message. A team member has started a project discussion.";
                        $view = View::make('email/adminProject', ['admin_name' => ucfirst($user->first_name) . ' ' . ucfirst($user->last_name), 'content' => $content]);
                        $mailMsg = $view->render();
                        send_mail($email, $mailSubject, $mailMsg);  
                        //echo"<br>". "mails sent on ".$email."<br>";        
                        $result[]="mails sent on ".$email;          
                            
                    }    
                    // dd(DB::getQueryLog()); // Show results of log               
                }else{
                    //echo"<br>".  "not in user notification";
                    $result[]="not in user notification";
                }
            }  
        }
          
        $data=ProjectCourse::select('fauser.id as fa_user_id','tauser.id as ta_user_id','fauser.first_name as fa_first_name','fauser.last_name as fa_last_name','fauser.email as fa_email','tauser.email as ta_email','tauser.first_name as ta_first_name','tauser.last_name as ta_last_name')->join('courses', 'courses.id', 'project_courses.course_id')
        ->leftjoin('university_users as fa', 'fa.id', 'courses.faculty_id')
        ->leftjoin('university_users as ta', 'ta.id', 'courses.ta_id')
        ->leftjoin('users as fauser', 'fauser.id', 'fa.user_id')
        ->leftjoin('users as tauser', 'tauser.id', 'ta.user_id')
        ->where('project_courses.id',$project_course_id)->first();
            $result=[];
              if($data->fa_user_id!=null)
                {
                    $userId=$data->fa_user_id;
                    $mailTo=$data->fa_email;
                    $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                                   ->where('notification_id', 10)
                                                   ->where('status', 1)->first();
                    if(isset($permissionCheck) && isset($data->fa_email))
                    {
                        if($permissionCheck->status)
                        {
                            if($data->fa_email)
                                {
                                    $mailSubject="A Team Started a Discussion";
                                    //$mailMsg="Dear ".ucfirst($data->fa_first_name).", Someone has started a Discussion in your project";
                                    $content = "This is an automated message. A team member has started a project discussion.";
                                    $view = View::make('email/adminProject', ['admin_name' => ucfirst($data->fa_first_name) . ' ' . ucfirst($data->fa_last_name), 'content' => $content]);
                                    $mailMsg = $view->render();
                                    send_mail($mailTo, $mailSubject, $mailMsg);  
                                    $result[]="Faculty Mail sent";                 
                                }
                        }                   
                    }

                }
                if($data->ta_user_id!=null)
                {
                    $userId=$data->ta_user_id;
                     
                    $mailTo=$data->ta_email;
                    $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                                   ->where('notification_id', 20)
                                                   ->where('status', 1)->first();
                    if(isset($permissionCheck) && isset($data->ta_email))
                    {
                        if($permissionCheck->status)
                        {
                            if($data->ta_email)
                                {
                                    $mailSubject="A Team Started a Discussion";
                                    //$mailMsg="Dear ".ucfirst($data->ta_first_name).", , Someone has started a Discussion in your project";
                                    $content = "This is an automated message. A team member has started a project discussion.";
                                    $view = View::make('email/adminProject', ['admin_name' => ucfirst($data->ta_first_name) . ' ' . ucfirst($data->ta_last_name), 'content' => $content]);
                                    $mailMsg = $view->render();
                                    send_mail($mailTo, $mailSubject, $mailMsg);   
                                    $result[]="Ta Mail sent";                 
                                }
                        }                   
                    }
                }
                
                return $result;
    }

    /**
    * Project Assign Instructor
    * @param Array $courses
    * @return Object
    */
    public function projectAssignInstructor($courses)
    {
        // DB::enableQueryLog();
        if($courses)
        {
            $users=array();
            foreach ($courses as $course) 
            {
                $data=ProjectCourse::select('fauser.id as fa_user_id','tauser.id as ta_user_id','fauser.first_name as fa_first_name','fauser.last_name as fa_last_name','fauser.email as fa_email','tauser.email as ta_email','tauser.first_name as ta_first_name','tauser.last_name as ta_last_name', 'courses.prefix', 'courses.number', 'courses.section', 'projects.title')
                ->join('courses', 'courses.id', 'project_courses.course_id')
                ->join('projects', 'projects.id', 'project_courses.project_id')
                ->leftjoin('university_users as fa', 'fa.id', 'courses.faculty_id')
                ->leftjoin('university_users as ta', 'ta.id', 'courses.ta_id')
                ->leftjoin('users as fauser', 'fauser.id', 'fa.user_id')
                ->leftjoin('users as tauser', 'tauser.id', 'ta.user_id')
                ->where('project_courses.course_id',$course)->first();
                if($data->fa_user_id!=null)
                {
                    $userId=$data->fa_user_id;
                    $mailTo=$data->fa_email;
                    $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                                ->where('notification_id', 12)
                                                ->where('status', 1)->first();
                    //   dd(DB::getQueryLog());
                    // dd($permissionCheck);
                    if(isset($permissionCheck) && isset($data))
                    {
                        if($permissionCheck->status)
                        {
                            if($data->fa_email)
                            {
                                $mailSubject="A Project Was Assigned to Your Course";
                                //$mailMsg="Dear ".ucfirst($data->fa_first_name).", ". Auth::user()->first_name ." has assigned ". $data->prefix.' '.$data->number.' '.$data->section. "in ".$data->title;
                                $content = "This is an automated message. The  project ".$data->title." has been assigned to your ".$data->prefix.' '.$data->number.' '.$data->section;
                                $view = View::make('email/adminProject', ['admin_name' => ucfirst($data->fa_first_name) . ' ' . ucfirst($data->fa_last_name), 'content' => $content]);
                                $mailMsg = $view->render();
                                send_mail($mailTo, $mailSubject, $mailMsg);
                                $users[]= $userId;                 
                            }  
                        }                                     
                    } 
                }

                if($data->ta_user_id!=null)
                {
                    $userId=$data->ta_user_id;
                    $mailTo=$data->ta_email;
                    $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                                ->where('notification_id', 22)
                                                ->where('status', 1)->first();
                    //   dd(DB::getQueryLog());
                    // dd($permissionCheck);
                    if(isset($permissionCheck) && isset($data))
                        {
                            if($permissionCheck->status)
                            {
                                if($data->ta_email)
                                    {
                                        $mailSubject="A Project Was Assigned to Your Course";
                                        //$mailMsg="Dear ".ucfirst($data->ta_first_name).", ". Auth::user()->first_name ." has assigned ". $data->prefix.' '.$data->number.' '.$data->section. "in ".$data->title;
                                        $content = "This is an automated message. The  project ".$data->title." has been assigned to your ".$data->prefix.' '.$data->number.' '.$data->section;
                                        $view = View::make('email/adminProject', ['admin_name' => ucfirst($data->ta_first_name) . ' ' . ucfirst($data->ta_last_name), 'content' => $content]);
                                        $mailMsg = $view->render();
                                        send_mail($mailTo, $mailSubject, $mailMsg);
                                        $users[]= $userId;                 
                                    }
                                
                            }
                                            
                        } 
                }
                 
            }
            return $users;
            
        }
        
    }


     

    /**
    * Spend Notification
    * @param Int $teamId, $spendType
    * @return Object
    */
    public function spendNotification($teamId, $spendType, $projectName)
    {
          // DB::enableQueryLog(); // Enable query log
            $datas=TeamStudent::with(['university_students.university_users'])->where('team_id', $teamId)->where('is_deleted', Config::get('constants.is_deleted.false'))->get();
            // dd(DB::getQueryLog());
            foreach ($datas as $data) 
            {
                if($spendType=='money')
                {
                    $studentpermissionNo=35;
                }
                if($spendType=='time')
                {
                $studentpermissionNo=36;
                }
                $user=$data->university_students->university_users;
                 $userId=$user->id;
                if($userId!=null)
                {
                    
                   $email=$user->email;
                   $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                                        ->where('status', 1)                               
                                                        ->where('notification_id', $studentpermissionNo)->first();
                    if(isset($permissionCheck))
                    {
                        if($permissionCheck->status==1)
                        {
                            $mailSubject='A '.ucfirst($spendType)." Was Added to the Project";
                            //$mailMsg="Dear ".ucfirst($user->first_name).", ".$spendType." has been added by ".Auth::user()->first_name;
                            $content = "This is an automated message. A ".$spendType." has been added for the project ".$projectName.'.';
                            $view = View::make('email/adminProject', ['admin_name' => ucfirst($user->first_name) . ' ' . ucfirst($user->last_name), 'content' => $content]);
                            $mailMsg = $view->render();
                            send_mail($email, $mailSubject, $mailMsg);  
                            echo"<br>". "mails sent on ".$email."<br>";                  
                                
                        }    
                        // dd(DB::getQueryLog()); // Show results of log               
                    }else{
                        echo"<br>".  "not in usernotification";
                    }
                   

                }  
            }
           
        
       
        //  DB::enableQueryLog();
         $data=Team::select('fauser.id as fa_user_id','tauser.id as ta_user_id','fauser.first_name as fa_first_name','fauser.last_name as fa_last_name','fauser.email as fa_email','tauser.email as ta_email','tauser.first_name as ta_first_name','tauser.last_name as ta_last_name')
         ->join('project_courses', 'project_courses.id', 'teams.project_course_id')
         ->join('courses', 'courses.id', 'project_courses.course_id')
         ->leftjoin('university_users as fa', 'fa.id', 'courses.faculty_id')
         ->leftjoin('university_users as ta', 'ta.id', 'courses.ta_id')
         ->leftjoin('users as fauser', 'fauser.id', 'fa.user_id')
         ->leftjoin('users as tauser', 'tauser.id', 'ta.user_id')
         ->where('teams.id',$teamId)->first();
        //   dd(DB::getQueryLog());
         // dd($data);
         $msg="";
         // DB::enableQueryLog();
         if($data->fa_user_id!=null)
         {
            if($spendType=='money')
            {
                $permissionNo=15;
            }
            if($spendType=='time')
            {
               $permissionNo=17;
            }
            $userId=$data->fa_user_id;
            $mailTo=$data->fa_email;
                    $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                            ->where('notification_id', $permissionNo)
                                            ->where('status', 1)->first();
                                         //    dd(DB::getQueryLog());
                    // dd($permissionCheck);
                    if(isset($permissionCheck) && isset($data))
                    {
                        if($permissionCheck->status)
                        {
                            if($data->fa_email)
                                {
                                    $mailSubject='A '.ucfirst($spendType)." Was Added to the Project";
                                    //$mailMsg="Dear ".ucfirst($data->fa_first_name).", ".$spendType." has been added by ".Auth::user()->first_name;
                                    $content = "This is an automated message. A ".$spendType." has been added for the project ".$projectName.'.';
                                    $view = View::make('email/adminProject', ['admin_name' => ucfirst($data->fa_first_name) . ' ' . ucfirst($data->fa_last_name), 'content' => $content]);
                                    $mailMsg = $view->render();
                                    send_mail($mailTo, $mailSubject, $mailMsg);
                                    $msg= "fa Mail Sent Successfully";                   
                                }else{
                                    $msg=  "fa email id not found";
                                }
                                
                        }else{
                            $msg=  "fa Permission Not Found"; 
                        }
                                          
                    }else{
                        $msg=  "fa data and Permission Not Found"; 
                    } 
         }

         if($data->ta_user_id!=null)
         {
            if($spendType=='money')
            {
                $permissionNo=25;
            }
            if($spendType=='time')
            {
               $permissionNo=27;
            }
            $userId=$data->ta_user_id;
            $mailTo=$data->ta_email;
            // DB::enableQueryLog();
            $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                            ->where('notification_id',  $permissionNo)
                                            ->where('status', 1)->first();
                                            // dd(DB::getQueryLog());
                    // dd($permissionCheck);
                   
                if(isset($permissionCheck) && isset($data))
                {
                    if($permissionCheck->status==1)
                    {
                        if($data->ta_email)
                            {
                                $mailSubject='A '.ucfirst($spendType)." Was Added to the Project";
                                //$mailMsg="Dear ".ucfirst($data->ta_first_name).", ".$spendType." has been added by ".Auth::user()->first_name;
                                $content = "This is an automated message. A ".$spendType." has been added for the project ".$projectName.'.';
                                $view = View::make('email/adminProject', ['admin_name' => ucfirst($data->ta_first_name) . ' ' . ucfirst($data->ta_last_name), 'content' => $content]);
                                $mailMsg = $view->render();
                                send_mail($mailTo, $mailSubject, $mailMsg);
                                $msg=  "ta Mail Sent Successfully";                   
                            }else{
                                $msg=  "ta email id not found";
                            }
                            
                    }else{
                        $msg=  "ta Permission Not Found";     
                    }
                                  
                }else{
                    $msg=  "ta data and Permission Not Found";
                } 
         }
         
        
         return $msg;
     
   
    }




    /**
    * Change Request Notifiation
    * @param Int $project_course_id
    * @return Object
    */
    public function crNotification($project_course_id, $cr)
    {
         // DB::enableQueryLog();
         if($project_course_id)
         {
             $data=ProjectCourse::select('fauser.id as fa_user_id','tauser.id as ta_user_id','fauser.first_name as fa_first_name','fauser.last_name as fa_last_name','fauser.email as fa_email','tauser.email as ta_email','tauser.first_name as ta_first_name','tauser.last_name as ta_last_name')
                 ->join('courses', 'courses.id', 'project_courses.course_id')
                 ->leftjoin('university_users as fa', 'fa.id', 'courses.faculty_id')
                 ->leftjoin('university_users as ta', 'ta.id', 'courses.ta_id')
                 ->leftjoin('users as fauser', 'fauser.id', 'fa.user_id')
                 ->leftjoin('users as tauser', 'tauser.id', 'ta.user_id')
                 ->where('project_courses.id',$project_course_id)->first();
                
                $projectData = ProjectCourse::with('projects')->where('id', $project_course_id)->first();
                $projectName = $projectData->projects->title;
                 if($data->fa_user_id!=null)
                 {
                    $userId=$data->fa_user_id;
                    $mailTo=$data->fa_email;
                    $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                                 ->where('notification_id', 16)
                                                 ->where('status', 1)->first();
                                                //   dd(DB::getQueryLog());
                    // dd($permissionCheck);
                    if(isset($permissionCheck) && isset($data))
                    {
                        if($permissionCheck->status)
                        {
                            if($data->fa_email)
                                {
                                    $mailSubject="A Change Request Submitted";
                                    //$mailMsg="Dear ".ucfirst($data->fa_first_name).", ".Auth::user()->first_name." has been added a Change Request, ".$cr;
                                    $content = "This is an automated message. A change request has been submitted for the project ".$projectName.".";
                                    $view = View::make('email/adminProject', ['admin_name' => ucfirst($data->fa_first_name) . ' ' . ucfirst($data->fa_last_name), 'content' => $content]);
                                    $mailMsg = $view->render();
                                    send_mail($mailTo, $mailSubject, $mailMsg);
                                    $msg="fa add change request successfully";                 
                                }else{
                                    $msg="fa add change request failed"; 
                                }
                        }else{
                            $msg="fa permission status failed"; 
                        }
                                        
                    }else{
                        $msg="fa data add permission failed"; 
                    } 
                 }

                 if($data->ta_user_id!=null)
                 {
                    $userId=$data->ta_user_id;
                    $mailTo=$data->ta_email;
                     $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                                 ->where('notification_id', 26)
                                                 ->where('status', 1)->first();
                                                //   dd(DB::getQueryLog());
                        // dd($permissionCheck);
                        if(isset($permissionCheck) && isset($data))
                        {
                            if($permissionCheck->status)
                            {
                                if($data->ta_email)
                                    {
                                        $mailSubject="A Change Request Submitted";
                                        //$mailMsg="Dear ".ucfirst($data->ta_first_name).", ".Auth::user()->first_name." has been added a Change Request, ".$cr;
                                        $content = "This is an automated message. A change request has been submitted for the project ".$projectName.".";
                                        $view = View::make('email/adminProject', ['admin_name' => ucfirst($data->ta_first_name) . ' ' . ucfirst($data->ta_last_name), 'content' => $content]);
                                        $mailMsg = $view->render();
                                        send_mail($mailTo, $mailSubject, $mailMsg);
                                        $msg="ta add change request sucess";                
                                    }else{
                                        $msg="ta add change request failed";
                                    } 
                            }else{
                                $msg="ta add change request permission failed";
                            }
                                            
                        } else{
                            $msg="ta permission and data add change request permission failed";
                        }
                 }
                
                  
             
            return $msg;
             
         }
    }


    /**
    * Milestone Mail Notification
    * @param Int $project_course_id
    * @param Sting $userType
    * @return Object
    */
    public function milestoneMailNotification($project_course_id, $userType, $mileName)
    {
            if($userType=='student')
            {
               $stpermissionNo=33;
            }
            if($userType=='client')
            {
                $stpermissionNo=34;
            }
            // dd($project_course_id, $userType);
            // DB::enableQueryLog(); // Enable query log
           $datas=TeamStudent::with(['university_students.university_users'])
           ->join('teams','teams.id','team_students.team_id')
           ->join('project_courses','project_courses.id','teams.project_course_id')->where('teams.project_course_id', $project_course_id)->get();
           
           foreach ($datas as $data) 
           {
               
               $user=$data->university_students->university_users;
                $userId=$user->id;
               if($userId!=null)
               {
                   
                  $email=$user->email;
                  $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                                       ->where('status', 1)                               
                                                       ->where('notification_id', $stpermissionNo)->first();
                   if(isset($permissionCheck))
                   {
                       if($permissionCheck->status==1)
                       {
                            $mailSubject="A Milestone Completed";
                            //$mailMsg="Dear ".ucfirst($user->first_name).", <br>Milestone, " .$mileName." is completed by ".Auth::user()->first_name;
                            $content = "This is an automated message. The milestone called ".$mileName." is completed by ".Auth::user()->first_name." ".Auth::user()->last_name.".";
                            $view = View::make('email/adminProject', ['admin_name' => ucfirst($user->first_name) . ' ' . ucfirst($user->last_name), 'content' => $content]);
                            $mailMsg = $view->render();
                            send_mail($email, $mailSubject, $mailMsg);  
                            //echo"<br>". "mails sent on ".$email."<br>";                  
                               
                       }    
                       // dd(DB::getQueryLog()); // Show results of log               
                   }else{
                       //echo"<br>".  "not in usernotification";
                   }
                  
   
               }  
           }
   
          
          if($project_course_id)
          {
            
              $data=ProjectCourse::select('fauser.id as fa_user_id','tauser.id as ta_user_id','fauser.first_name as fa_first_name','fauser.last_name as fa_last_name','fauser.email as fa_email','tauser.email as ta_email','tauser.first_name as ta_first_name','tauser.last_name as ta_last_name')
                  ->join('courses', 'courses.id', 'project_courses.course_id')
                  ->leftjoin('university_users as fa', 'fa.id', 'courses.faculty_id')
                  ->leftjoin('university_users as ta', 'ta.id', 'courses.ta_id')
                  ->leftjoin('users as fauser', 'fauser.id', 'fa.user_id')
                  ->leftjoin('users as tauser', 'tauser.id', 'ta.user_id')
                  ->where('project_courses.id',$project_course_id)->first();
                 
                   
                  if($data->fa_user_id!=null)
                 {
                    if($userType=='student')
                    {
                    $permissionNo=13;
                    }

                    if($userType=='client')
                    {
                        $permissionNo=14;
                    }
                   
                    $userId=$data->fa_user_id;
                    $mailTo=$data->fa_email;
                    
                    $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                              ->where('notification_id', $permissionNo)
                                              ->where('status', 1)->first();
                     if(isset($permissionCheck) && isset($data))
                    {
                        if($permissionCheck->status)
                        {
                            if($data->fa_email)
                                {
                                    $mailSubject="A Milestone Completed";
                                    //$mailMsg="Dear ".ucfirst($data->fa_first_name).", <br>Milestone, " .$mileName." is completed by ".Auth::user()->first_name;
                                    $content = "This is an automated message. The milestone called ".$mileName." is completed by ".Auth::user()->first_name." ".Auth::user()->last_name.".";
                                    $view = View::make('email/adminProject', ['admin_name' => ucfirst($data->fa_first_name) . ' ' . ucfirst($data->fa_last_name), 'content' => $content]);
                                    $mailMsg = $view->render();
                                    send_mail($mailTo, $mailSubject, $mailMsg);
                                    $msg=1;                 
                                }else{
                                    $msg=2;  
                                }   
                        }else{
                            $msg=3;  
                        }             
                    }else{
                        $msg=4;  
                    } 
                 }

                 if($data->ta_user_id!=null)
                 {
                    if($userType=='student')
                    {
                    $permissionNo=23;
                    }

                    if($userType=='client')
                    {
                        $permissionNo=24;
                    }
                   
                    $userId=$data->ta_user_id;
                    $mailTo=$data->ta_email;
                    $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                    ->where('notification_id', $permissionNo)
                    ->where('status', 1)->first();
                    if(isset($permissionCheck) && isset($data))
                    {
                        if($permissionCheck->status)
                        {
                            if($data->ta_email)
                                {
                                    $mailSubject="A Milestone Completed";
                                    //$mailMsg="Dear ".ucfirst($data->ta_first_name).", <br>Milestone, " .$mileName." is completed by ".Auth::user()->first_name;
                                    $content = "This is an automated message. The milestone called ".$mileName." is completed by ".Auth::user()->first_name." ".Auth::user()->last_name.".";
                                    $view = View::make('email/adminProject', ['admin_name' => ucfirst($data->ta_first_name) . ' ' . ucfirst($data->ta_last_name), 'content' => $content]);
                                    $mailMsg = $view->render();
                                    send_mail($mailTo, $mailSubject, $mailMsg);
                                    $msg=5;                  
                                }
                                $msg=6;  
                        }
                        $msg=7;                
                    } 
                 } 
                 $msg=8;  
              
          }
          return $msg;
    }
    
   /**
    * Message Mail Notification
    * @param Int $project_course_id
    * @param Sting $email, $name
    * @return Object
    */
    public function messageMailNotification($userId, $email, $name)
    {
          if($userId)
          { 
           $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                            ->whereIn('notification_id', [11,21,32])
                                            ->where('status', 1)->first();
           
                if(isset($permissionCheck)&& isset($permissionCheck->status))
                { 
                    if($email)
                        {
                            $mailSubject="A Message Was Sent to You";
                            //$mailMsg="Dear ".ucfirst($name).", ".Auth::user()->first_name." send you a message";
                            $content = "This is an automated message. ".Auth::user()->first_name." ".Auth::user()->last_name." has sent you a message.";
                            $view = View::make('email/adminProject', ['admin_name' => ucfirst($name), 'content' => $content]);
                            $mailMsg = $view->render();
                            send_mail($email, $mailSubject, $mailMsg);
                            return true;                 
                        }
                    return "email not found";              
                } 
            return "details not found";  
          }
    }


    /**
    * Mail Send Complte Task
    * @param Int $taskId
    * @return Object
    */
    public function mailSentCompleteTask($taskId, $team_id)
    { 
          //DB::enableQueryLog(); // Enable query log
        $datas=TeamStudent::with(['university_students.university_users'])
        ->join('team_tasks','team_tasks.team_id','team_students.team_id')->where('team_tasks.task_id', $taskId)->where('team_tasks.team_id', $team_id)->get();
         //dd(DB::getQueryLog());
         //dd($datas);
        foreach ($datas as $data) 
        {
            
            $user=$data->university_students->university_users;
             $userId=$user->id;
            if($userId!=null)
            {
                
               $email=$user->email;
               $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                                    ->where('status', 1)                               
                                                    ->where('notification_id', 37)->first();
                if(isset($permissionCheck))
                {
                    if($permissionCheck->status==1)
                    {
                        $taskName=Task::where('id', $taskId)->first();
                        $mailSubject="A Project Task completed";
                        //$mailMsg="Dear ".ucfirst($user->first_name).",<br> Task, ".$taskName->title." is completed by ".Auth::user()->first_name;
                        $content = "This is an automated message. ".Auth::user()->first_name." ".Auth::user()->last_name." has completed the ".$taskName->title." task";
                        $view = View::make('email/adminProject', ['admin_name' => ucfirst($user->first_name) . ' ' . ucfirst($user->last_name), 'content' => $content]);
                        $mailMsg = $view->render();
                        send_mail($email, $mailSubject, $mailMsg);  
                        echo"<br>". "mails sent on ".$email."<br>";                  
                            
                    }    
                    // dd(DB::getQueryLog()); // Show results of log               
                }else{
                    echo"<br>".  "not in usernotification";
                }
               

            }  
        }

        
        $data=Task::select('fauser.id as fa_user_id','tauser.id as ta_user_id','fauser.first_name as fa_first_name','fauser.last_name as fa_last_name','fauser.email as fa_email','tauser.email as ta_email','tauser.first_name as ta_first_name','tauser.last_name as ta_last_name')
              ->join('project_courses','project_courses.id','tasks.project_course_id')
              ->join('courses', 'courses.id', 'project_courses.course_id')
              ->leftjoin('university_users as fa', 'fa.id', 'courses.faculty_id')
              ->leftjoin('university_users as ta', 'ta.id', 'courses.ta_id')
              ->leftjoin('users as fauser', 'fauser.id', 'fa.user_id')
              ->leftjoin('users as tauser', 'tauser.id', 'ta.user_id')
              ->where('tasks.id',$taskId)->first();
             
              if($data->fa_user_id!=null)
              {
                 $userId=$data->fa_user_id;
                 $mailTo=$data->fa_email;
                 $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                              ->where('notification_id', 18)
                                              ->where('status', 1)->first();
                                             //   dd(DB::getQueryLog());
                 // dd($permissionCheck);
                 if(isset($permissionCheck) && isset($data))
                 {
                     if($permissionCheck->status)
                     {
                         if($data->fa_email)
                             {
                                 $taskName=Task::where('id', $taskId)->first();
                                 $mailSubject="A Project Task completed";
                                 //$mailMsg="Dear ".ucfirst($data->fa_first_name).", Task, ".$taskName->title." is completed by ".Auth::user()->first_name;
                                 $content = "This is an automated message. ".Auth::user()->first_name." ".Auth::user()->last_name." has completed the ".$taskName->title." task";
                                 $view = View::make('email/adminProject', ['admin_name' => ucfirst($data->fa_first_name) . ' ' . ucfirst($data->fa_last_name), 'content' => $content]);
                                 $mailMsg = $view->render();
                                 send_mail($mailTo, $mailSubject, $mailMsg);
                                 $msg=1;                 
                             }else{
                                $msg=2;
                             }
                               
                     }else{
                        $msg=3;
                     }
                                         
                 }else{
                    $msg=4;
                 } 
              }

              if($data->ta_user_id!=null)
              {
                 $userId=$data->ta_user_id;
                 $mailTo=$data->ta_email;
                  $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                              ->where('notification_id', 28)
                                              ->where('status', 1)->first();
                                             //   dd(DB::getQueryLog());
                     // dd($permissionCheck);
                     if(isset($permissionCheck) && isset($data))
                     {
                         if($permissionCheck->status)
                         {
                             if($data->ta_email)
                                 {
                                    $taskName=Task::where('id', $taskId)->first();
                                    $mailSubject="A Project Task completed";
                                    //$mailMsg="Dear ".ucfirst($data->ta_first_name).", Task, ".$taskName->title." is completed by ".Auth::user()->first_name;
                                    $content = "This is an automated message. ".Auth::user()->first_name." ".Auth::user()->last_name." has completed the ".$taskName->title." task";
                                    $view = View::make('email/adminProject', ['admin_name' => ucfirst($data->ta_first_name) . ' ' . ucfirst($data->ta_last_name), 'content' => $content]);
                                    $mailMsg = $view->render();
                                    send_mail($mailTo, $mailSubject, $mailMsg);
                                    $msg=5;                  
                                 }else{
                                    $msg=6;
                                 }
                                  
                         }else{
                            $msg=7;
                         }
                                         
                     }else{
                        $msg=8;
                     } 
              }

        return $msg;
    }
    
    /**
    *  Check role is active or not
    * @param Int $student
    * @return Object
     */
    public function roleIsActive($student = null)
    {
        $result = User::select('roles.status as roleIsActive')->where('users.id', $student)
        ->join('roles', 'roles.id', 'users.role_id')->first();
        return $result;
    }

    /**
    * Peer Evaluation Mail Notification
    * @param Int $project_course_id
    * @param Sting $userType
    * @return Object
    */
    public function peerEvaluationMailNotification($project_course_id)
    {
        $stpermissionNo=39;
        $datas=TeamStudent::with(['university_students.university_users'])
        ->join('teams','teams.id','team_students.team_id')
        ->join('project_courses','project_courses.id','teams.project_course_id')->where('teams.project_course_id', $project_course_id)->get();
        //dd($datas);
        foreach ($datas as $data) 
        {            
            $user=$data->university_students->university_users;
            $userId=$user->id;
            if($userId!=null)
            {                
                $email=$user->email;
                $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                    ->where('status', 1)                               
                    ->where('notification_id', $stpermissionNo)->first();
                if(isset($permissionCheck))
                {
                    if($permissionCheck->status==1)
                    {
                        $mailSubject="Complete Peer Evaluation";
                        //$mailMsg="Dear ".ucfirst($user->first_name).", <br>Peer evaluation is started by ".Auth::user()->first_name;
                        $content = "This is an automated message. Please login and complete the peer evaluation.";
                        $view = View::make('email/adminProject', ['admin_name' => ucfirst($user->first_name) . ' ' . ucfirst($user->last_name), 'content' => $content]);
                        $mailMsg = $view->render();
                        send_mail($email, $mailSubject, $mailMsg);  
                        echo"<br>". "mails sent on ".$email."<br>";    
                    }              
                }else{
                    echo"<br>".  "not in usernotification";
                }
                

            }  
        }
    }

    /**
    * Facuty Complete Milestone Mail Notification
    * @param Int $project_course_id
    * @param Sting $userType
    * @return Object
    */
    public function facutyCompleteMilestoneMailNotification($project_course_id, $mileName, $userType)
    {
        $stpermissionNo=4;
        $datas = ProjectCourse::with('projects.client.university_users')->where('id', $project_course_id)->get();
        
        foreach ($datas as $data) 
        {            
            $user=$data->projects->client->university_users;
            $userId=$user->id;
            if($userId!=null)
            {                
                $email=$user->email;
                $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                    ->where('status', 1)                               
                    ->where('notification_id', $stpermissionNo)->first();
                if(isset($permissionCheck))
                {
                    if($permissionCheck->status==1)
                    {
                        $mailSubject="Course Instructor Reviewed the Milestone";
                        //$mailMsg="Dear ".ucfirst($user->first_name).", <br>Milestone, ".$mileName." is completed by ".Auth::user()->first_name;
                        $content = "This is an automated message. The course instructor has reviewed the milestone called ".$mileName;
                        $view = View::make('email/adminProject', ['admin_name' => ucfirst($user->first_name) . ' ' . ucfirst($user->last_name), 'content' => $content]);
                        $mailMsg = $view->render();
                        send_mail($email, $mailSubject, $mailMsg);    
                    }              
                }else{
                    //echo"<br>".  "not in usernotification";
                }
                

            }  
        }
    }
}
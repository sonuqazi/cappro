<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use App\Mail\addProject;
use Illuminate\Support\Facades\Mail;
use App\Models\Project;
use App\Models\ProjectCourse;
use App\Models\Team;
use App\Models\TeamStudent;
use App\Models\ProjectRequest;
use App\Models\UserNotification;
use App\Models\Categories;
use App\Models\Task;
use App\Models\Message;
use App\Models\EvaluationQuestionStar;

use App\Services\ProjectService;
use App\Services\UserProfileService;
use App\Services\UploadFilesService;
use App\Services\UserService;
use App\Services\MessageService;
use App\Services\TeamService;
use App\Services\EvaluationStartService;
use App\Services\EmailTemplateService;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Organization;
use App\Models\ChangeRequest;
use App\Models\EvaluationStart;
use App\Models\PeerEvaluationRating;
use App\Models\PeerEvaluationRatingStar;
use App\Models\PeerEvaluationStart;
use App\Models\ProjectCategory;
use App\Models\ProjectCourseSetting;
use App\Models\PmPlan;
use App\Traits\ProjectTrait;
use App\Traits\RoleTrait;
use View;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Str;
use App\Mail\AdminUser;

class ProjectController extends Controller
{

    use ProjectTrait;
    use RoleTrait;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
 

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage Users', ['only' => ['view_users','load_student','delete_student','unassign_student', 'search_all_users','update_student_data','unassign_student','delete_student']]);
        $this->middleware('permission:Manage New Project Request', ['only' => ['add','edit', 'delete']]);
        $this->middleware('permission:Manage Courses', ['only' => ['view_courses','is_course_number_valid','add_new_course','assign_courses','assign_student_course','faculty_assign_courses','deleteCourse','editProjectCourse']]);
        $this->middleware('permission:Manage Course Plans', ['only' => ['view_plans','search_all_plans','update_pm_plans','delete_pm_plan','assign_pm_plan']]);
        $this->middleware('permission:Manage Teams', ['only' => ['all_teams', 'team_members']]);
        $this->projectObj = new ProjectService();
        $this->userProfileObj = new UserProfileService(); // user profile Service object
        $this->uploadFileObj = new UploadFilesService(); // user profile Service object
        $this->userObj = new UserService(); //  user Service object
        $this->messageObj = new MessageService(); // user message Service object
        $this->uploadFileObj = new UploadFilesService(); // upload file Service object
        $this->teamObj = new TeamService(); // user profile Service object
        $this->evalStartObj = new EvaluationStartService(); // evaluation start Service object
        $this->emailTemplateObj = new EmailTemplateService(); // email template Service object 
    }
 
    /**
     * List of all projects
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        
        //     $user = auth()->user();
        //     $user_id=$user->university_users->id;
        //     $roleId = auth()->user()->role_id;
        //     $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
        //     //dd($user_profile_data);
        //     if($roleId == '2'){
        //         $projects = Project::with(['project_course'])->get()->where('client_id', $user_id)->sortByDesc("id");
        //     }else{
        //         // DB::enableQueryLog();
        //         $projects = $this->projectObj->getProjects($user_id, $roleId);
        //         // dd(DB::getQueryLog());
        //     }
        //    return view('projects.index', ['projects' => $projects, 'user_profile_data' => $user_profile_data]);
         $category = isset($_GET['category']) ? $_GET['category'] : '';
         $sort = isset($_GET['sort']) ? $_GET['sort'] : '';
         $date = isset($_GET['date']) ? $_GET['date'] : '';
         $user=Auth()->user();
         $user_id = $user->id; 
         $university_userId= $user->university_users->id;
         $categories = $this->projectObj->getProjectCategories();
        $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
        $role=Auth::user()->role_id;
        if($role==2)
        {
            $projects['proposed']  = $this->projectObj->fetchAllProjects(0, $category, $sort, $date, $university_userId, $role);
            $projects['approved']  = $this->projectObj->fetchAllProjects(1, $category, $sort, $date, $university_userId, $role);
            $projects['active']  = $this->projectObj->fetchAllProjects(2, $category, $sort, $date, $university_userId, $role);
            $projects['rejected'] = $this->projectObj->fetchAllProjects(4, $category, $sort, $date, $university_userId, $role);
            $projects['completed'] = $this->projectObj->fetchAllProjects(5, $category, $sort, $date, $university_userId, $role); 
            
        }elseif($role==4)
        {
            $projects['active']  = $this->projectObj->fetchAllStudentProjects(2, $category, $sort, $date, $university_userId, $role);
            $projects['completed'] = $this->projectObj->fetchAllStudentProjects(5, $category, $sort, $date, $university_userId, $role); 
            
        }
        //dd($projects['active'][0]);
         if(Auth::user()->hasanyrole(['Client']))
        {
          return view('projects.index', ['projects' => $projects, 'user_profile_data' => $user_profile_data, 'categories' => $categories]);
        }elseif(Auth::user()->hasanyrole(['Student'])){
            return view('projects.myProject', ['projects' => $projects, 'user_profile_data' => $user_profile_data, 'categories' => $categories]);
        }else{
            return redirect()->route('home')->with('error', 'Not Authorized');
        }
    }


      /**
     *   fetch project by project course id
     * 
     * @param  int  $v
     * @return \Illuminate\Http\Response
     */
    
    public function getProjectIdByProjectCourseId($projectCourseId)
    {
        return $this->projectObj->fetchProjectByProjectCourseId($projectCourseId);
    }
    /**
     * Create project
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function add(Request $request)
    {
        try {
            $redirect = Auth::user()->role_id  == 2 ? 'allProject' : 'allAdminProject';
            $isProjectExist =  $this->projectObj->isProjectExist($request->title, $request->client_id);
            if($isProjectExist > 0){
                if(Auth::user()->role_id == 2){
                    return redirect()->route($redirect)->with('error', $request->title. ' already added.');
                }else{
                    return redirect()->route($redirect)->with('error', $request->title. ' already added for selected client.');
                }
            }
            //  $category_name = Categories::where('id', $request['categories_id'])->get();
            $userUniverId = $this->userProfileObj->getUserUniversityID($request['user_id']);
            $universityUserId = $userUniverId->id;
            $email = Auth::user()->email;
            if($request['created_at']){
                $created_at = $request['created_at'];
            }else{
                $created_at = date('Y-m-d H:i:s');
            }
            if($request['updated_at']){
                $updated_at = $request['updated_at'];
            }else{
                $updated_at = date('Y-m-d H:i:s');
            }
            $data = array(
                'categories_id' => $request['categories_id'][0],
                'title' => $request['title'],
                'description' => $request['description'],
                'background' => $request['background'],
                'justification' => $request['justification'],
                'deliverable' => $request['deliverable'],
                //'approved' => 0, 
                'status' => 0,
                'client_id' => $request->has(['client_id']) ? $request['client_id'] : $universityUserId,
                'created_by' => $universityUserId,
                'updated_by' => $universityUserId,
                'created_at' => $created_at,
                'updated_at' => $updated_at
            );
            $userData = '';
            $userData = array(
                'username' =>  Auth::user()->first_name . ' ' . Auth::user()->last_name,
                'category_name' => ''
            );

            $project_id = Project::create($data);
            if(!empty($request->file('file'))){
                if(count($request->file('file')) > 1){
                    foreach($request->file('file') as $key => $fileData){
                        
                        $fileType = '';
                        $mime = $fileData->getMimeType();
                        
                        $mimeType = explode('/', $mime);
                        if($mimeType[0] == 'application'){
                            $file = explode('.', $mimeType[1]);
                            $fileType = array_pop($file);
                        }else{
                            $fileType = $mimeType[0];
                        }
                        
                        $files['file'] = $fileData;

                        $upload_file = $this->uploadFileObj->uploadFile($files, 'project', auth()->user()->id, $project_id->id, 'project_files', $fileType, '', $created_at, $updated_at);
                    }
                }else{
                    
                    if ($request->file('file') !== '' && !empty($request->file('file'))) {
                        //get file mime type
                        $fileType = '';
                        $mime = $request->file('file')[0]->getMimeType();
                        $mimeType = explode('/', $mime);
                        if($mimeType[0] == 'application'){
                            $file = explode('.', $mimeType[1]);
                            $fileType = array_pop($file);
                        }else{
                            $fileType = $mimeType[0];
                        }
                        $files['file'] = $request->file('file')[0];
                        $upload_file = $this->uploadFileObj->uploadFile($files, 'project', auth()->user()->id, $project_id->id, 'project_files', $fileType, '', $created_at, $updated_at);
                    }
                }
            }

            if (!empty($request->categories_id)) {
                $this->projectObj->save_project_category($request->categories_id, $project_id->id, $created_at, $updated_at);
            }
            $notifications = $this->userObj->user_notification('A project is submitted');
            // dd($notifications->userNotification);
            $client_name = Auth::user()->first_name . ' ' . Auth::user()->last_name;
            foreach ($notifications->userNotification as $notification) {
                if ($notification->status == 1) {
                    $admin = User::where('id', $notification->user_id)->first();
                    $mailTo = $admin->email;
                    $mailSubject = 'A New Project Submitted';
                    $content = 'This is an automated message. A new project has been submitted by ' . $client_name . '.';
                    $view = View::make('email/adminProject', ['admin_name' => $admin->first_name . ' ' . $admin->last_name, 'content' => $content]);
                    $mailMsg = $view->render();
                    send_mail($mailTo, $mailSubject, $mailMsg);
                }
            }
            if (Mail::to($email)->send(new addProject($userData))) {
                return redirect()->route($redirect)->with('success', 'Project added successfully.');
            } else {
                return redirect()->route($redirect)->with('success', 'Project added successfully. But email not sent because your email address is not verified.');
            }
        } catch (Throwable $e) {
            report($e);
            return redirect()->route($redirect)->with('error', "Something went wrong");
        }
    }

    /**
     * View project
     *
     * @param  int  $id|$projectCourseId
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function view($id = null, $projectCourseId=null)
    {
        try {
            if($projectCourseId){
                $projectId = $this->projectObj->getProjectId($projectCourseId);
                $project_files = $this->projectObj->getProjectsFiles($projectId);
            }else{
                $projectId = $id;
                $project_files = $this->projectObj->getProjectsFiles($id);;
            }
            $projects = $this->projectObj->getProjectsDetails($projectId);
            $project_details = $this->projectObj->getProjectOtherDetails($projectId);
            $project_client_details = $this->projectObj->getProjectClientDetails($projectId);
            if(!$project_client_details){
                return redirect()->back()->with('error', 'Client not assigned for '.$projects->title.' project.');
            }
            $categories = $this->projectObj->getProjectCategories();
            // if ( $projects == false ) {
            // return redirect()->route('projects.view')->with('error', "ID is not valid");
            // }
            
            // $projects = $this->projectObj->getProjectFullDetails($id);
            $user = Auth::user();
            $user_profile_data = $this->userProfileObj->getUserProfile($user->id);
            $projectList = $this->projectObj->getProjects($user->university_users->id, $user->role_id);
            return view('projects.view', ['project' => $projects, 'user_profile_data' => $user_profile_data, 'project_files' => $project_files, 'project_details' => $project_details, 'project_client_details' => $project_client_details, 'categories' => $categories, 'roleId' => $user->role_id, 'projectCourseId' => $projectCourseId, 'projectList' => $projectList]);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('projects.view')->with('error', $e);
        }
    }

    /**
     * Edit project
     *
     * @param \Illuminate\Http\Request  $request
     * @param int $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(Request $request, $id)
    {
        $save_project_category = $request->categories_id;
        try {
            $data = array(
                'title' => $request['title'],
                'description' => $request['description'],
                'background' => $request['background'],
                'justification' => $request['justification'],
                'deliverable' => $request['deliverable'],
                //'approved' => 0,
                'status' => 0,
                'created_by' => $request['user_id'],
                'client_id' => $request['client_id'],
                'updated_by' => $request['user_id'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            );
            Project::where('id', $id)->update($data);
            if (!empty($save_project_category)) {
                ProjectCategory::where('project_id', $id)->delete();
                $this->projectObj->save_project_category($request->categories_id, $id);
            }
            return redirect()->route('allProject')->with('success', 'Project updated successfully.');
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('allProject')->with('error', $e);
        }
    }

    /**
     * Delete project
     * 
     * @param \Illuminate\Http\Request  $request
     * @param int $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function delete(Request $request, $id)
    {
        try {
            Project::where('id', $id)->delete();
            return redirect()->route('allProject')->with('success', 'Project deleted successfully.');
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('allProject')->with('error', $e);
        }
    }


    /**
     * Update profile is usr not fill 2nd step in registration
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update_profile_details(Request $request)
    {
        //dd($request->all());
        try {
            $extraMsg = '';
            if (empty($request['org_id']) && Auth::user()->role_id == 2) {
                $org =  Organization::create(['name' => $request['organization_name'], 'updated_at' => $request['updated_at'], 'created_at' => $request['updated_at']]);
                $request['org_id'] = $org->id;
            }

            User::where('id', $request['user_id'])->update([
                'first_name' => $request['first_name'],
                'last_name' => $request['last_name'],
                'user_name' => $request['user_name'],
                'email' => $request['email'],
                'status' => 1,
                'updated_at' => $request['updated_at'],
            ]);
            if($request['email'] != $request['old_email']){
                $user['email_verification_token'] = Str::random(32);
                $user['first_name'] = $request['first_name'];
                $user['role_id'] = $request['role_id'];
                User::where('id', $request['user_id'])->update([
                    'email_verification_token' => $user['email_verification_token'],
                    'status' => 0,
                ]);
                $user = json_decode(json_encode($user), FALSE);
                Mail::to($request['email'])->send(new AdminUser($user));
                if (Auth::user()->role_id == 2) {
                    $extraMsg = ' Your Email has been changed and a verification Email has been sent to you. Please verify it.';
                }
            }
            if (Auth::user()->role_id == 2) {
                $profile =  UserProfile::where('id', $request['user_profiel_id'])->update([
                    'user_id' => $request['user_id'],
                    'org_id' => $request['org_id'],
                    'phone' => $request['phone'],
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
                    'profile_bio' => $request['profile_bio'],
                    'academic_field' => $request['academic_field'],
                    'updated_at' => $request['updated_at'],
                ]);
            }
            $msg = 'Your profile details updated successfully.'.$extraMsg;
            session()->put('success',$msg);
            return redirect()->back();
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('home')->with('error', $e);
        }
    }

    /**
     * View Change Request on admin
     * @param $id
     * @return
     */
    // public function project_change_request($id = null)
    // {
    //     $user =  Auth::user();
    //     $requests = $this->getProjectChangeRequests($id, request()->course);
    //     $courses = $this->getallCourses();
    //     $user_profile_data = $this->userProfileObj->getUserProfile($user->id);
    //     $projects = $this->projectObj->getProjects($user->id, 1);
    //     return view('projects.change-requests.index', ['requests' => $requests, 'user_profile_data' => $user_profile_data, 'projects' => $projects, 'courses' => $courses]);
    // }

    /**
     * View Change Request on admin
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function project_change_request($id = null)
    {
        $user =  Auth::user();
        $user_profile_data = $this->userProfileObj->getUserProfile($user->id);
        $requests = $this->getChangeRequests($user_profile_data->university_users->id, $id);
        $projects = $this->projectObj->getProjects($user->university_users->id, $user->role_id);
        return view('projects.change-requests.index', ['requests' => $requests, 'user_profile_data' => $user_profile_data, 'projects' => $projects, 'project_course_id' => $id]);
    }

    /**
     * View Change Request
     * 
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function change_request($id = null)
    {
        $user =  Auth::user();
        $user_profile_data = $this->userProfileObj->getUserProfile($user->id);
        //  || $user->role_id == Config::get('constants.roleTypes.faculty')|| $user->role_id == Config::get('constants.roleTypes.ta')
        if ($user->role_id == Config::get('constants.roleTypes.admin')) {
            if(isset($id)){
                $requests = $this->getChangeRequests('', $id);
            }else{
                $requests = [];
            }
        } else {
            if(isset($id)){
                $requests = $this->getChangeRequests($user_profile_data->university_users->id, $id);
            }else{
                $requests = [];
            }
        }
      
       
        $userUniverId = $this->userProfileObj->getUserUniversityID($user->id);
        $universityUserId = $userUniverId->id;
        
        $projects = $this->projectObj->getProjects($universityUserId, $user->role_id);        
        // dd($requests );

        $studentsList = $this->studentsList();

        return view('projects.change-requests.index', ['requests' => $requests, 'user_profile_data' => $user_profile_data, 'projects' => $projects, 'studentsList' => $studentsList, 'universityUserId' => $universityUserId, 'project_course_id' => $id]);
    }



    /**
     * Add Change Request
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function add_change_request(Request $request)
    {
            $data = array(
                'title' => $request['title'],
                'project_course_id' => $request['project_course_id'],
                'description' => $request['description'],
                'status' => 0,
                'created_by' => Auth::user()->university_users->id,
                'created_at' => $request['created_at'],
                'updated_at' => $request['updated_at']
            );
            $changeRequest =  $this->addChangeRequest($data);
            $cr= $changeRequest->id;
            if($cr)
            {
                $user=Auth::user();
                if ($user->role_id == Config::get('constants.roleTypes.client'))
                {
                   $res= $this->userObj->crNotification($request['project_course_id'], $request['title']);  
                //    dd($res);  
                }
            }
        if ($request->file('file') !== '' && !empty($request->file('file'))) {
            //get file mime type
            $fileType = '';
            $mime = $request->file('file')->getMimeType();
            $mimeType = explode('/', $mime);
            if($mimeType[0] == 'application'){
                $file = explode('.', $mimeType[1]);
                $fileType = array_pop($file);
            }else{
                $fileType = $mimeType[0];
            }
            $upload_file = $this->uploadFileObj->uploadCRfile($request->file(), 'change-request', auth()->user()->id, $cr, 'change-request', $fileType, '', $request['created_at'], $request['updated_at']);
        }        
        return redirect()->back()->with('success', 'Change request added successfully.');
    }


    /**
     * Delete Change Request
     * @param \Illuminate\Http\Request  $request
     * @param int $id|$fileId
     * @return \Illuminate\Http\Response
     */
    public function delete_change_request(Request $request, $id, $fileId=null)
    {
        $changeRequest = $this->deleteChangeRequest($id, $fileId);
        return redirect()->back()->with('success', 'Change request closed successfully.');
    }

    /**
     * Undo Change Request
     * @param \Illuminate\Http\Request  $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function undo_change_request(Request $request, $id, $dateTime)
    {

        $changeRequestUndo = $this->undoChangeRequest($id, $dateTime);
        return redirect()->back()->with('success', 'Decision undone successfully.');
    }

    /**
     * Edit Change Request 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function edit_change_request(Request $request)
    {
        $data = array(
            'title' => $request['title'],
            'project_course_id' => $request['project_course_id'],
            'description' => $request['description'],
            'status' => 0,
            'created_by' => Auth::user()->university_users->id,
            'updated_at' => $request['updated_at']
        );

        $changeRequest = $this->editChangeRequest($data, $request->id);
        if ($request->file('file') !== '' && !empty($request->file('file'))) {
            //get file mime type
            $fileType = '';
            $mime = $request->file('file')->getMimeType();
            $mimeType = explode('/', $mime);
            if($mimeType[0] == 'application'){
                $file = explode('.', $mimeType[1]);
                $fileType = array_pop($file);
            }else{
                $fileType = $mimeType[0];
            }
            $upload_file = $this->uploadFileObj->uploadCRfile($request->file(), 'change-request', auth()->user()->id, $request->id, 'change-request', $fileType, $request->fileId, $request['updated_at'], $request['updated_at']);
        }
        return redirect()->back()->with('success', 'Change request updated successfully.');
    }

    /**
     * Edit Change Request 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function approve_deny_change_request(Request $request)
    {
        $data = array(
            'status' => $request['status'],
            'resolution' => $request['resolution'],
            'resolved_by' => $request['resolved_by'],
            'updated_at' => $request['updated_at'],
            'resolved_at' => $request['updated_at']
        );
        if($request['status'] ==1){
            $entityType = 'change-request-approved';
        }else{
            $entityType = 'change-request-deny';
        }
        $changeRequest = $this->editChangeRequest($data, $request->id);
        if ($request->file('file') !== '' && !empty($request->file('file'))) {
            //get file mime type
            $fileType = '';
            $mime = $request->file('file')->getMimeType();
            $mimeType = explode('/', $mime);
            if($mimeType[0] == 'application'){
                $file = explode('.', $mimeType[1]);
                $fileType = array_pop($file);
            }else{
                $fileType = $mimeType[0];
            }
            $upload_file = $this->uploadFileObj->uploadFile($request->file(), $entityType, auth()->user()->id, $request->id, 'change-request', $fileType, false, $request['updated_at'], $request['updated_at']);
        }
        return redirect()->back()->with('success', 'Change request updated successfully.');
    }

    /**
     * navigat to upload files in project/teams
     *
     * @param $project_course_id|$team_id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function files($project_course_id = null, $team_id = null)
    {
        
        $team_arr = $team_files = [];
        try {
            $user_id = auth()->user()->id;
            $role_id = auth()->user()->role_id;
            $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
            $universityUserId = $user_profile_data->university_users->id;
            $projects = $this->projectObj->getProjects($universityUserId, $role_id);
            $teams_details = (!empty($project_course_id)) ? $this->projectObj->getProjectTeams($project_course_id, $universityUserId, $role_id, $isDeleted=2) : [];

            if (!empty($teams_details)) {
                foreach ($teams_details as $count_team => $team_detail_arr) {
                    $team_arr[$team_detail_arr->course_name][] = $team_detail_arr;
                }
            }

            if (!empty($team_id)) {
                $team_files = $this->projectObj->getTeamFiles($team_id);
            }else{
                $team_files = $this->projectObj->getProjectCourseFiles($project_course_id);
            }

            return view('files.index', compact('projects', 'user_profile_data', 'team_arr', 'project_course_id', 'team_id', 'team_files', 'universityUserId', 'role_id'));
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('projects')->with('error', $e);
        }
    }

    /**
     * get the teams from project id. 
     * Uses ajax request on files via change project dropdown
     *
     * @param \Illuminate\Http\Request  $request
     * @return JSON
     */

    public function fetch_teams(Request $request)
    {
        if ($request->ajax()) {
            $role_id = auth()->user()->role_id;
            // isDeleted =2, fetch all data true and false
            $teams_details =  $this->projectObj->getProjectTeams($request->project_course_id, $request->created_by, $role_id, $isDeleted=2, $request->type);
            if (!empty($teams_details)) {
                foreach ($teams_details as $count_team => $team_detail_arr) {
                    $team_arr[$team_detail_arr->course_name][] = $team_detail_arr;
                }
            }
            $return['listing'] = (!empty($team_arr)) ? $team_arr : [];
            return response()->json($return);
        }
    }

    /**
     * get the student from storage. 
     * Uses ajax request on files via change project dropdown
     *
     * @param \Illuminate\Http\Request  $request
     * @return JSON
     */
    public function getAdminChat(Request $request)
    {
        $adminMsg =  $this->projectObj->getAdminChat();
        $return['admin'] = (!empty($adminMsg)) ? $adminMsg : [];
        return response()->json($return);
    }
    /**
     * get the student from storage. 
     * Uses ajax request on files via change project dropdown
     *
     * @param \Illuminate\Http\Request  $request
     * @return JSON
     */
    public function student_list(Request $request)
    {
        //dd($request->team_id);
        if ($request->ajax()) {
            $client = '';
            $linked_users = [];
            $team_students =  $this->projectObj->getChatTeamStudents($request->team_id);
            if(Auth::user()->role_id != 2){
                $client =  $this->projectObj->getChatClient($request->projectCourseId);
            }
            $instructor =  $this->projectObj->getChatInstructor($request->projectCourseId);
            //dd($client);
            $adminMsg =  $this->projectObj->getChatAdmin($request->projectCourseId);
            $user =  Auth::user();
            if (!empty($team_students)) {
                // foreach ($team_students as $count_team => $team_detail_arr) {
                //     $team_arr[$team_detail_arr->course_name][] = $team_detail_arr;
                // }
                foreach ($team_students as $key => $users) {
                    $linked_users[$key]['id'] = $users->id;
                    $linked_users[$key]['first_name'] = $users->first_name;
                    $linked_users[$key]['last_name'] = $users->last_name;
                    $linked_users[$key]['profile_image'] = $users->profile_image;
                    $linked_users[$key]['role_name'] = $users->role_name;
                    $linked_users[$key]['project_id'] = $users->project_id;
                    $linked_users[$key]['team_id'] = $users->team_id;
                    $msgCount = $this->messageObj->message_count($users->id, $user->university_users->id);
                    if ($msgCount > 0) {
                        $unreadMsgCount = $msgCount;
                        $newClass = 'msg-bg-color';
                    } else {
                        $unreadMsgCount = $newClass = '';
                    }
                    $linked_users[$key]['msg_count'] = $unreadMsgCount;
                    $linked_users[$key]['unreadMsgClass'] = $newClass;
                }
            }
            $newArray = '';
            if($client){
                $newArray = array_merge($linked_users, $client);
            }else{
                $newArray = $linked_users;
            }
            if($instructor){
                $newArray = array_merge($newArray, $instructor);
            }
            //dd($newArray);
            $return['listing'] = (!empty($newArray)) ? $newArray : [];
            $return['admin'] = (!empty($adminMsg)) ? $adminMsg : [];
            return response()->json($return);
        }
    }

    /**
     * add attach ments in files
     * @param \Illuminate\Http\Request  $request
     * @param $team_id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function add_files(Request $request, $team_id)
    {
        try {
            if ($request->file('file') !== '' && !empty($request->file('file'))) {
                //get file mime type
                $fileType = '';
                $mime = $request->file('file')->getMimeType();
                $mimeType = explode('/', $mime);
                if($mimeType[0] == 'application'){
                    $file = explode('.', $mimeType[1]);
                    $fileType = array_pop($file);
                }else{
                    $fileType = $mimeType[0];
                }
                $upload_file = $this->uploadFileObj->uploadFile($request->file(), 'team_files', auth()->user()->id, $team_id, 'team_files', $fileType, false, $request['created_at'], $request['updated_at']);
               echo $upload_file;
            }
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('allProject')->with('error', "error");
        }
    }

    /**
     * add attached files for all teams
     * @param \Illuminate\Http\Request  $request
     * @param $team_id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function add_files_for_all_teams(Request $request)
    {
        try {
            $teams = $this->projectObj->getTeamsForFiles($request->project_course_id);
            
            if ($request->file('file') !== '' && !empty($request->file('file'))) {
                //get file mime type
                $fileType = '';
                $mime = $request->file('file')->getMimeType();
                $mimeType = explode('/', $mime);
                if($mimeType[0] == 'application'){
                    $file = explode('.', $mimeType[1]);
                    $fileType = array_pop($file);
                }else{
                    $fileType = $mimeType[0];
                }
                $upload_file = $this->uploadFileObj->uploadFile($request->file(), 'team_files', auth()->user()->id, $teams[0]['team_id'], 'team_files', $fileType, false, $request['created_at'], $request['updated_at']);
                
            }
            
            $fileData = $this->uploadFileObj->getFileData($upload_file);
            
            $insertValue = array_slice($teams, 1);
            
            foreach($insertValue as $key => $value){
                
                $data = array(
                    'name' => $fileData['name'],
                    'entity_id' => $value['team_id'],
                    'entity_type' => $fileData['entity_type'],
                    'location' => $fileData['location'],
                    'description' => '',
                    'mime_type' => $fileData['mime_type'],
                    'created_by' => $fileData['created_by'],
                    'created_at' => $fileData['created_at'],
                    'updated_at' => $fileData['updated_at'],
                    'is_visibleToStudents' => $fileData['is_visibleToStudents']
                );
                $file[] = $this->addFiles($data);
                // send notification mail to team students.
                $mailNotification = $this->uploadFileObj->sendMailNotification($value['team_id']);
            }
            return redirect()->route('allFiles', $request->project_course_id)->with('success', 'Team file uploaded successfully.');
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('allProject')->with('error', "error");
        }
    }

    /**
     * delete attachments in files
     * @param $file_id
     * @param $file_name
     * @return \Illuminate\Http\Response
     */
    public function delete_files($file_id)
    {
        $user_id = auth()->user()->id;
        $teams_details =  $this->projectObj->deleteTeamFiles($file_id, $user_id);
        return redirect()->back()->with('success', 'File deleted successfully.');
    }

    /**
     * delete attachments in files
     * @param $file_id
     * @return \Illuminate\Http\Response
     */
    public function deleteCRFile($file_id)
    {
        $fileDetails =  $this->projectObj->deleteCRFile($file_id);
        return $fileDetails;
    }

    /**
     * share files with client
     * @param $file_id
     * @param $file_name
     * @return \Illuminate\Http\Response
     */
    public function share_files($file_id)
    {
        $user_id = auth()->user()->id;
        $isShare =  $this->projectObj->shareFileWithClient($file_id, $user_id);
        if($isShare == 1){
            $msg = 'File shared successfully.';
        }else{
            $msg = 'File unshared successfully.';
        }
        return redirect()->back()->with('success', $msg);
    }


    /**
     * update the description of file
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update_file_description(Request $request)
    {
        //if ($request->ajax()) {
            $this->projectObj->save_edit_description($request->file_id, $request->description);
            return redirect()->back()->with('success', 'File description updated.');
            //return response()->json(true);
        //}
    }

    /**
     * get more project listing
     * 
     * @param \Illuminate\Http\Request  $request
     * @return Json
     */
    public function get_more_projects(Request $request)
    {
        if ($request->ajax()) {
            $this->projectObj->get_more_projects($request->file_id, $request->description);
            return response()->json(true);
        }
    }


    /**
     * funtion to search all unisersityh users
     * 
     * @param \Illuminate\Http\Request  $request
     * @return Json
     */
    public function search_all_users(Request $request)
    {

        $user_id = auth()->user()->id;
        if ($request->ajax() && !empty($user_id)) {

           

            $return['recordsFiltered'] = $return['recordsTotal'] = $this->projectObj->search_all_user_counts($request);
            $return['draw'] = $request->draw;
            $view_users = $this->projectObj->search_all_users($request);

            //$data = $this->projectObj->getHeading($request->course_id);
            //$return['heading'] = 'Upload Students '.$data->semester.' '.$data->year.' - '.$data->prefix.' '.$data->number.' '.$data->section;
            
            foreach ($view_users as $key => $user) {
                if ($request->course_id != '' and $request->display_type == '') {
                    $actionOption = '<span class="mx-1">|</span><a class="unassign-student-detail del-active" course-id = "' . $request->course_id . '" onclick="unassign_student(' . $user->id . ',' . $request->course_id . ')">Unassign</a>';
                } else {
                    $actionOption = '<span class="mx-1">|</span><a class="delete-student-detail del-active" delete-id = "' . $user->id . '" onclick="delete_student(' . $user->id . ')">Deactivate</a>';
                }
                $data[$key]['user_name'] = '<span class="edit-student-username-' . $user->id . '">' . $user->user_name . '</span>';
                $data[$key]['first_name'] = '<span class="edit-student-first-name-' . $user->id . '">' . $user->first_name . '</span>';
                $data[$key]['last_name'] = '<span class="edit-student-last-name-' . $user->id . '">' . $user->last_name . '</span>';
                $data[$key]['email'] = '<a class="edit-student-email-' . $user->id . ' del-active openEmailTemplate" data-client-email="'.$user->email.'" data-text="You are sending email to '.$user->first_name.' '.$user->last_name.' at '.$user->email.'">' . $user->email . '</a>';
                $data[$key]['role_id'] = $user->role_id . '<span class="edit-student-role-' . $user->id . ' hidden">' . $user->role_assigned . '</span>';
                $data[$key]['edit'] = '<a class="edit-university-user" currentCourseId= "'.$request->course_id.'" edit-id = "' . $user->id . '" >Edit</a>' . $actionOption;
                $data[$key]['id'] = $user->id;
                $data[$key]['DT_RowId'] = "row_".$user->id;
            }

            $return['data'] = !empty($data) ? $data : array();
            return response()->json($return);
        }
    }

    /**
     * view the list of unisersity users 
     * @param int $course_id
     * @return \Illuminate\Http\Response
     */
    public function view_users($course_id = null)
    {
        $heading = $semester_id = '';
        $user_id = auth()->user()->id;
        $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
        $roles = $this->getRoles();
        $all_semesters = $this->projectObj->getAllSemesters();
        //dd($course_id);
        if($course_id){
            $semester = $this->projectObj->getSemester($course_id);
            $semester_id = $semester->id;
        }
        //dd($semester_id);
        $all_courses = $this->projectObj->getSemesterCourses($semester_id);
        $emailTemplateList = $this->emailTemplateObj->getAllActiveEmailTemplate();
        if($course_id){
            $data = $this->projectObj->getHeading($course_id);
            $heading = 'Upload Students to '.$data->semester.' '.$data->year.' - '.$data->prefix.' '.$data->number.' '.$data->section;
        }
        return view('projects.users.index', compact('user_profile_data', 'user_id', 'course_id', 'all_courses', 'roles', 'heading', 'all_semesters', 'semester_id', 'emailTemplateList'));
    }


    /**
     * Upload the students from xl file sent from ajax
     * @param \Illuminate\Http\Request  $request
     * @return json
     */
    public function upload_student_files(Request $request)
    {
        if ($request->ajax()) {
            $user_student_data = $this->uploadFileObj->uploadStudentBulkFile($request, auth()->user()->id);
            return response()->json(
                [
                    "response" => $user_student_data
                ],
                200
            );
        }
    }



    /**
     * @access public
     * @desc Function udpate
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update_student_data(Request $request)
    {
        if ($request->ajax()) {
            $user_student_data = $this->projectObj->update_student_data($request, auth()->user()->id);
        }
    }


    /**
     * view the list of added courses in semister
     * @param int $semester_id
     * @return \Illuminate\Http\Response
     */
    public function view_courses($semester_id = null)
    {
        
        $user_id = auth()->user()->id;
        $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
        if(isset($semester_id)){
        $courses = $this->projectObj->getSemesterCourses($semester_id);
        }else{
            $courses = [];
        }
        $allFacultyUsers = $this->projectObj->getFacultyUsers();
        $allSemesters = $this->projectObj->getAllSemesters();
        $role_id = auth()->user()->role_id;

        $formatedCourse = [];
        foreach ($courses as $key => $value) {
            $formatedCourse[$key]['id'] = $value->id;
            $formatedCourse[$key]['number'] = '<span id="course_number_' . $value->id . '">' . $value->number . '</span>';
            $formatedCourse[$key]['description'] = '<span id="course_description_' . $value->id . '">' . $value->description . '</span>';
            $formatedCourse[$key]['header'] = '<span id="course_header_' . $value->id . '">' . $value->prefix .' '.$value->number.' '.$value->section. '</span>';
            $formatedCourse[$key]['prefix'] = '<span id="course_prefix_' . $value->id . '">' . $value->prefix .'</span>';
            $formatedCourse[$key]['section'] = '<span id="course_section_' . $value->id . '">' . $value->section . '</span>';
            $formatedCourse[$key]['student_count'] = $value->student_count;
            $formatedCourse[$key]['faculty_user'] = '<span id="course_faculty_user_' . $value->id . '">' . $value->faculty_user . ' ' . $value->faculty_user_last_name . '</span>';
            $formatedCourse[$key]['faculty_id'] = $value->faculty_id;
            $formatedCourse[$key]['ta_id'] = $value->ta_id;
            $formatedCourse[$key]['semester_id'] = $value->semester_id;
        }

        return view('projects.courses.index', compact('user_profile_data', 'user_id', 'formatedCourse', 'allFacultyUsers', 'allSemesters', 'semester_id', 'role_id'));
    }


    /**
     * validate if course number is availale
     * @param \Illuminate\Http\Request  $request 
     * @return JSON
     */
    public function is_course_number_valid(Request $request)
    {

        if ($request->ajax()) {
            $is_valid = $this->projectObj->isCourseNumberVAlid($request);
            if (count($is_valid) > 0) {
                $number = false;
            } else {
                $number = true;
            }

            return response()->json($number, 200);
        }
    }

    /**
     * save a new course to db/ update
     * @param \Illuminate\Http\Request  $request
     * @return JSON
     */
    public function add_new_course(Request $request)
    {
         $user_id = auth()->user()->id;
            $is_valid = $this->projectObj->isCourseNumberVAlid($request);
            if(isset($is_valid->id))
            {
                if ($is_valid->id!=$request->id) {
                     return redirect()->route('viewCourses', $request->semester_id)->with('error', 'Course already exits');
                }else{
                    $add_new_course = $this->projectObj->addNewCourse($request, $user_id);
                     
                    return redirect()->route('viewCourses', $request->semester_id)->with('success', 'Course updated successfully');
                } 
            }else {
                $add_new_course = $this->projectObj->addNewCourse($request, $user_id);
                return redirect()->route('viewCourses', $request->semester_id)->with('success', 'Course added successfully');
            }
         
    }

    /**
     * view the list of added pmplans
     * @param 
     * @return \Illuminate\Http\Response
     */
    public function view_plans()
    {
        $user_id = auth()->user()->id;
        $semester_id = null;
        $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
        return view('projects.plans.index', compact('user_profile_data', 'user_id'));
    }


    /**
     * funtion to search all pm plans added
     * 
     * @param \Illuminate\Http\Request  $request
     * @return Json
     */
    public function search_all_plans(Request $request)
    {
        $user_id = auth()->user()->id;
        if ($request->ajax() && !empty($user_id)) {
            $return['recordsFiltered'] = $return['recordsTotal'] = $this->projectObj->search_all_plans_counts($request);
            $return['draw'] = $request->draw;
            $view_plans = $this->projectObj->search_all_plans($request);
            //dump($view_plans);die;
            $deleteOption = '';
            $editOption = '';
            foreach ($view_plans as $key => $plan) {
                if ($plan->is_active != 1 && Auth::user()->role_id == 1) {
                    $deleteOption = '<span class="mx-1">|</span><a class="delete-plan-detail del-active" delete-id = "' . $plan->id . '" onclick="delete_this_plan(' . $plan->id . ')">Delete</a>';
                }
                if (Auth::user()->role_id == 1) {
                    $editOption = '| <a class="edit-plan-detail del-active" edit-id = "' . $plan->id . '" is-active = "' . $plan->is_active . '">Edit</a>';
                }
                if($plan->is_active != 1 && $plan->created_by == Auth::user()->university_users->id){
                   $deleteOption = '<span class="mx-1">|</span><a class="delete-plan-detail del-active" delete-id = "' . $plan->id . '" onclick="delete_this_plan(' . $plan->id . ')">Delete</a>';
                }
                if($plan->created_by == Auth::user()->university_users->id){
                    $editOption = '| <a class="edit-plan-detail del-active" edit-id = "' . $plan->id . '" is-active = "' . $plan->is_active . '">Edit</a>';
                }
                $data[$key]['pm_plans_name'] = '<span id="edit-plan-name-' . $plan->id . '">' . $plan->name . '</span>';
                $data[$key]['pm_plans_description'] = '<span id="edit-plan-description-' . $plan->id . '">' . $plan->description . '</span>';
                $data[$key]['milestone_count'] = '<span>' . $plan->milestone_count . '</span>';
                $data[$key]['owner'] = $plan->usercreated_by->university_users->first_name.' '.$plan->usercreated_by->university_users->last_name.'  <br>'.date('F d, Y H:i A', strtotime($plan->created_at)); 
                if(!empty($plan->userupdated_by)){
                    $data[$key]['lastUpdate'] = $plan->userupdated_by->university_users->first_name.' '.$plan->userupdated_by->university_users->last_name.'  <br>'.date('F d, Y H:i A', strtotime($plan->updated_at));
                }else{
                    $data[$key]['lastUpdate'] = $plan->usercreated_by->university_users->first_name.' '.$plan->usercreated_by->university_users->last_name.'  <br>'.date('F d, Y H:i A', strtotime($plan->created_at)); 
                }
                $data[$key]['edit'] = '<a class="view-plan-detail del-active" view-id = "' . $plan->id. '">View</a> '.$editOption.' | <a class="copy-plan-detail del-active" copy-id = "' . $plan->id. '">Clone</a>' . $deleteOption; //.($plan->is_active == "0") ? $deleteOption : '';
                $data[$key]['id'] = $plan->id;
                $deleteOption = '';
                $editOption = '';
            }

            $return['data'] = !empty($data) ? $data : array();
            return response()->json($return);
        }
    }

    /**
     * @access public
     * @desc Function udpate pm plan/save pm plan
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update_pm_plans(Request $request)
    {
        if ($request->ajax()) {
            $user_student_data = $this->projectObj->update_pm_plans($request, auth()->user()->id);
            //return redirect()->route('viewPlans')->with('success', $user_student_data);
            return response()->json(array('response' => 'success', 'url' => url('/project/view_plans')));
        }else{
            $user_student_data = $this->projectObj->update_pm_plans($request, auth()->user()->id);
            return redirect()->route('viewPlans')->with('success', $user_student_data);
        }
    }

    /**
     * @access public
     * @desc Function fetch all milestones added in apm plan
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function get_all_milestones(Request $request)
    {
        if ($request->ajax()) {
            $user_student_data = $this->projectObj->get_all_milestones_with_plans($request);
            //dd($user_student_data);
        }
        return response()->json($user_student_data);
    }

    /**
     * List of all projects for admin
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function list_all_projects($semId = null)
    {
            $category = isset($_GET['category']) ? $_GET['category'] : '';
            $sort = isset($_GET['sort']) ? $_GET['sort'] : '';
            $date = isset($_GET['date']) ? $_GET['date'] : '';
            $user_id = auth()->user()->id;
            $categories = $this->projectObj->getProjectCategories();
            $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
            $projects['proposed']  = $this->projectObj->getAllProjects(0, $category, $sort, $date);
            $projects['approved']  = $this->projectObj->getAllProjects(1, $category, $sort, $date);
            //dd($projects['approved']);
            $projects['active']  = $this->projectObj->getAllProjects(2, $category, $sort, $date, '', $semId);
            // dd($projects['active']);
            $projects['archived'] = $this->projectObj->getAllProjects(3, $category, $sort, $date);
            $projects['rejected'] = $this->projectObj->getAllProjects(4, $category, $sort, $date);
            $projects['completed'] = $this->projectObj->getAllProjects(5, $category, $sort, $date);
            $allSemesters = $this->projectObj->getAllSemesters();
            $allPmPlans = $this->projectObj->getAllPmPlans();
            $communicationSetting = $this->projectObj->getCommunicationSettings();
            $evaluationList = $this->evalStartObj->evaluationList();
            $peerEvaluationList = $this->projectObj->peerEvaluationList();
            $emailTemplateList = $this->emailTemplateObj->getAllActiveEmailTemplate();
            // dd($emailTemplateList);
            if(Auth::user()->hasanyrole(['Admin','Faculty','TA']))
            {
                return view('projects.all-projects', ['projects' => $projects, 'user_profile_data' => $user_profile_data, 'categories' => $categories, 'allSemesters' => $allSemesters, 'allPmPlans' => $allPmPlans, 'communicationSetting' => $communicationSetting, 'evaluationList' => $evaluationList, 'semId' => $semId, 'peerEvaluationList' => $peerEvaluationList, 'emailTemplateList' => $emailTemplateList]);
       
            }else{
                return redirect()->route('home')->with('error', 'Not Authorized');
            }
         
            
        
    }

    /**
     * Change Status of project
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function changeStatus(Request $request)
    {
        try {

            $data = array(
                'status' => $request->status,
                'updated_at' => $request->updated_at
            );
            $this->projectObj->changeStatus($request->id, $data, $request->projectCourseId);
            $client = $this->projectObj->getClientName($request->id);
            if ($request->status == 0) {
                $status = 'Proposed';
            } else if ($request->status == 1) {
                $status = 'Approved';
            } else if ($request->status == 3) {
                $status = 'Archived';
            } else if ($request->status == 4) {
                $status = 'Rejected';
            } else if ($request->status == 5) {
                $status = 'Completed';
            }
            /**
             * As per the client(Vivek) information don't sent status mail to project client
             */
            // $mailTo = $client->usercreated_by->university_users->email;
            // $mailSubject = 'Project Status Updated';
            // $content = 'This is an automated message. The (' . $client->title . ') status has been changed to ' . $status . ' .';
            // $view = View::make('email/adminProject', ['admin_name' => $client->usercreated_by->university_users->first_name . ' ' . $client->usercreated_by->university_users->last_name, 'content' => $content]);
            // $mailMsg = $view->render();
            // send_mail($mailTo, $mailSubject, $mailMsg);
            return redirect()->back()->with('success', 'Project status changed successfully.');
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('allAdminProject')->with('error', $e);
        }
    }


   


    /**
     * @access public
     * @delete_pm_plan Function  to delete pm plan
     * @param int $plan_id
     * @return \Illuminate\Http\Response
     */
    public function delete_pm_plan($plan_id, $dateTime)
    {
        if ($plan_id) {
            $plan_data = $this->projectObj->delete_pm_plan($plan_id, $dateTime);
        }
        return $plan_data;
    }

    /**
     * Get courses according to semester
     * @param \Illuminate\Http\Request  $request
     * @return JSON
     */

    public function get_courses(Request $request)
    {
        if ($request->ajax()) {
            $courses =  $this->projectObj->getCourses($request->sem_id);
            $return['listing'] = (!empty($courses)) ? $courses : [];            
            return response()->json($return);
        }
    }

    /**
     * Get courses according to semester
     * @param \Illuminate\Http\Request  $request
     * @return JSON
     */

    public function get_other_courses(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            
            $courses =  $this->projectObj->getOtherCourses($data);
            $return['listing'] = (!empty($courses)) ? $courses : [];            
            return response()->json($return);
        }
    }

    /**
     * Assign project to course
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function assign_courses(Request $request)
    {
        $data  = $request->all();
        //dd($data);
        $courses =  $this->projectObj->assignCourses($data);
        $client = $this->projectObj->getClientName($request->project_id);
        $coursesName =  $this->projectObj->assignCoursesName($request->courses_id);
        $mailTo = $client->usercreated_by->university_users->email;
        $mailSubject = 'Project Status Updated';
        $content = 'This is an automated message. The (' . $client->title . ') has been assigned to course '.$coursesName.'.';
        if($request['courses_id'])
        {
           $this->userObj->projectAssignInstructor($request['courses_id']);
        }
        $view = View::make('email/adminProject', ['admin_name' => $client->usercreated_by->university_users->first_name . ' ' . $client->usercreated_by->university_users->last_name, 'content' => $content]);
        $mailMsg = $view->render();
        send_mail($mailTo, $mailSubject, $mailMsg);
        return redirect()->back()->with('success', 'Project status changed successfully.');
        //return redirect()->route('allAdminProject')->with('success', 'Project status changed successfully.');
    }

    /**
     * All teams from storage
     * @param int $project_course_id|$isDeleted
     * @return \Illuminate\Http\Response
     */
    public function all_teams($project_course_id = null, $isDeleted=false)
    {
        $projectData=$this->projectObj->getProjectCoursebyProjectCourseId($project_course_id);
        $project_id=$project_course_id; 
        $teamMembers = $milestones_files = $team_arr = $discussions = [];
      
            $user = Auth::user();
            $user_id=$user->university_users->id;
            $role_id = auth()->user()->role_id;
            $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
            $projects = $this->projectObj->getProjects($user_id, $role_id);
           
            $teams_details = (!empty($project_id)) ? $this->projectObj->getProjectTeams($project_course_id, $user_id, $role_id, $isDeleted) : [];
            // dd($teams_details);
            if (!empty($teams_details)) {
                foreach ($teams_details as $count_team => $team_detail_arr) {
                    $teamMembers[$count_team]['team_id'] = $team_detail_arr->team_id;
                    $teamMembers[$count_team]['name'] = $team_detail_arr->team_name;
                    $teamMembers[$count_team]['members'] = $this->projectObj->getTeamMembers($team_detail_arr->team_id);
                    $teamMembers[$count_team]['total_members'] = count($this->projectObj->getTeamMembers($team_detail_arr->team_id));
                }
            }

            //dd($teamMembers);
            //dd($discussions);
            
            return view('projects.all_teams', compact('user_profile_data', 'teamMembers', 'projects', 'project_id', 'team_arr', 'user_id', 'project_course_id', 'isDeleted', 'role_id'));
        
    }

    /**
     * Team members from storage
     * @param int $team_id|$project_id
     * @return \Illuminate\Http\Response
     */
    public function team_members($project_id = null, $team_id = null)
    {
        $teamFaculty = $teamTA = $teamStudent = [];
        try {
            $user_id = auth()->user()->id;
            $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
            $teamMembers = $this->projectObj->getTeamMembers($team_id);
            $facultyTa=$this->projectObj->getFacultyTaByTeamId($team_id);
            //  dd($facultyTa);
            foreach ($teamMembers as $key => $teamMember) {
                if ($teamMember->role_id == '3') {
                    $teamFaculty[] = $teamMember;
                } elseif ($teamMember->role_id == '4') {
                    $teamStudent[] = $teamMember;
                } elseif ($teamMember->role_id == '5') {
                    $teamTA[] = $teamMember;
                }
            }

            //dd($teamStudent);
            return view('projects.teamMembers', compact('user_profile_data', 'teamMembers', 'teamFaculty','facultyTa', 'teamTA', 'teamStudent', 'team_id', 'user_id', 'project_id'));
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('allTeams')->with('error', $e);
        }
        //dd($teamStudent);
    }

    /**
     * Assign Pm Plan to a project
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function assign_pm_plan(Request $request)
    {
        $milestones = $this->projectObj->getMilestones($request->plan_id);
        $teams = $this->projectObj->getTeamsByProjectCourseId($request->project_course_id);
        if (count($teams) == 0) {
            return redirect()->back()->with('error', 'No team assigned to this.');
        }
        $user_id = auth()->user()->id;
        $university_user = $this->teamObj->getUniversityUserId($user_id);
        foreach ($milestones as $key => $milestone) {
            $updateDeadline = $this->projectObj->updateDeadline($milestone->id, $request['end_date'][$key]);
            foreach ($teams as $team) {
                $arr = explode('-', $request['end_date'][$key]);
                $newDate = $arr[2].'-'.$arr[0].'-'.$arr[1];
                $data = array(
                    'milestone_id' => $milestone->id,
                    'team_id' => $team->id,
                    'status' => 1,
                    'end_date' => $newDate,
                    'created_by' => $university_user->id,
                    'created_at' => $request['updated_at'],
                    'updated_by' => $university_user->id,
                    'updated_at' => $request['updated_at'],
                );
                $assign_milestone = $this->projectObj->assignMilestone($data);
                if ($assign_milestone) {
                    $this->projectObj->setPmPlanActive($request->plan_id, $request['updated_at']);
                }
            }
        }
        return redirect()->back()->with('success', 'Course Plan assigned successfully.');
    }
    /**
     * List of assigned projects to faculty
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function assigned_projects()
    {
        try {
            $user_id = auth()->user()->id;
            $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
            $projects = $this->projectObj->assignedProjects($user_id);
            $peerEvaluationList = $this->projectObj->peerEvaluationListForFacultyAndTA();
            $evaluationList = $this->evalStartObj->evaluationList();
            $allPmPlans = $this->projectObj->getAllPmPlans();
            $communicationSetting = $this->projectObj->getCommunicationSettings();
            //dd($communicationSetting);
            return view('projects.assigned_projects', ['projects' => $projects, 'user_profile_data' => $user_profile_data, 'peerEvaluationList' => $peerEvaluationList, 'allPmPlans' => $allPmPlans, 'evaluationList' => $evaluationList, 'communicationSetting' => $communicationSetting]);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('home')->with('error', $e);
        }
    }

    /**
     * List of completed projects to TA
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function completed_projects()
    {
        try {
            $user_id = auth()->user()->id;
            $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
            $projects = $this->projectObj->completedProjects($user_id);
            $evaluationList = $this->evalStartObj->evaluationList();
            //dd($communicationSetting);
            return view('projects.completed_projects', ['projects' => $projects, 'user_profile_data' => $user_profile_data, 'evaluationList' => $evaluationList]);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('home')->with('error', $e);
        }
    }

    /**
     * Assign student to courses
     * @param int $student|$course
     * @return Json
     */
    public function unassign_student($student = null, $course = null)
    {
        try {
            $result = $this->projectObj->unassignStudent($student, $course);
            //dd($projects);
            return response()->json($result);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('home')->with('error', $e);
        }
    }

    /**
     * Delete student from storage
     * @param int $student
     * @return \Illuminate\Http\Response
     */
    public function delete_student($student = null, $dateTime = null)
    {
        try {
            $result = $this->projectObj->deleteStudent($student, $dateTime);
            //dd($projects);
            return response()->json($result);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('home')->with('error', $e);
        }
    }

    /**
     * Load unassined students list\
     *
     * @param \Illuminate\Http\Request  $request
     * @return Json
     */
    public function load_student(Request $request)
    {
        $user_id = $request->user_id;
        $user =  Auth::user();
        $courseStudentList = $this->projectObj->courseStudentList($request->course_id);
        $returnHTML = view('projects.student_list')->with(['courseStudentList' => $courseStudentList, 'user' => $user])->render();
        return response()->json(array('response' => 'success', 'html' => $returnHTML));
    }

    /**
     * Load unassined students list\
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function assign_student_course(Request $request)
    {
        try {
            $user_id = Auth::user()->university_users->id;
            $course_id = $request->course_id;
            $i = 0;
            foreach ($request->student_id as $key => $student) {
                $data[$i]['course_id'] = $course_id;
                $data[$i]['student_id'] = $key;
                $data[$i]['created_by'] = $user_id;
                $data[$i]['created_at'] = $request->created_at;
                $i++;
            }
            $this->projectObj->addStudentCount($course_id, count($request->student_id));
            $result = $this->projectObj->assignCourseToStudent($data);
            return redirect()->route('viewUsers', $course_id)->with('success', 'Students assigned to course successfully');
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('viewUsers')->with('error', $e);
        }
    }

    /**
     * get approved projects
     *
     * @return \Illuminate\Http\Response
     */
    public function approved_projects()
    {
        try {
            $category = $sort = $date = '';
            $user_id = auth()->user()->id;
            $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
            $projects = $this->projectObj->getAllProjects(1, $category, $sort, $date, 1);
            $allSemesters = $this->projectObj->getAllSemesters();
            //dd($projects);
            return view('projects.approved_projects', ['projects' => $projects, 'user_profile_data' => $user_profile_data, 'allSemesters' => $allSemesters]);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('home')->with('error', $e);
        }
    }

    /**
     * faculty sssign project to course
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function faculty_assign_courses(Request $request)
    {
        $data  = $request->all();
        $courses =  $this->projectObj->assignCourses($data);
        return redirect()->route('approvedProjects')->with('success', 'Project assigned to course successfully.');
    }

    /**
     * Revoke project from pool
     * @param \Illuminate\Http\Request  $request
     * @return Json
     */
    public function revoke_project(Request $request)
    {
        $data = [];
        $user = Auth::user();
        $data['status'] = '3';
        $data['updated_by'] = $user->university_users->id;
        $data['updated_at'] = $request->dateTime;
        $result =  $this->projectObj->revokeProject($data, $request->id);
        //dd($result['status']);
        return response()->json(array('status' => $result['status'], 'msg' => $result['msg']));
    }

    /**
     * Request for project from pool
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function request_project(Request $request)
    {
        $data = [];
        $user = Auth::user();
        //dd($user->university_users);
        $data['project_id'] = $request->project_id;
        $data['university_user_id'] = $user->university_users->id;
        $data['note'] = $request->note;
        $data['created_by'] = $user->university_users->id;
        $data['created_at'] = $request->updated_at;
        $data['updated_at'] = $request->updated_at;
        $project =  $this->projectObj->requestProject($data);
        //dd($project);
        //return response()->json(array('response' => 'success', 'msg' => $project));
        return redirect()->back()->with('success', $project);
    }

    /**
     * Get all requested projects
     * @return \Illuminate\Http\Response
     */
    public function all_requested_projects()
    {
        try {
            $user_id = Auth::user()->id;
            $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
            $projects = $this->projectObj->allRequestedProjects();
            //dd($projects);
            return view('projects.requested_projects', ['projects' => $projects, 'user_profile_data' => $user_profile_data]);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('requested_projects')->with('error', $e);
        }
    }

    /**
     * Get all requested projects
     * 
     * @param \Illuminate\Http\Request  $request
     * @return Json
     */
    public function approve_deny_project_request(Request $request)
    {
        $data = [];
        $user = Auth::user();
        //dd($request);
        $data['status'] = $request->status;
        $data['updated_by'] = $user->university_users->id;
        $data['updated_at'] = Carbon::now();
        $result =  $this->projectObj->approveDenyProjectRequest($data, $request->id);
        return response()->json($result);
    }

    /**
     * Assign project to pool
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function project_assign_for_faculty_pool(Request $request)
    {
        try {
            $i = 0;
            //Get active faculties
            $facultyList = $this->projectObj->getAllFacultiesForEmail();
            if($request->is_assigned){
                foreach ($request->is_assigned as $key => $value) {
                    $data[] = $key;
                    $i++;
                }
                $result = $this->projectObj->projectAssignForFacultyPool($data, $request->updated_at);
                //send mail to faculty
                foreach ($facultyList as $facultyData) {
                    $mailTo = $facultyData->email;
                    $mailSubject = 'Assigned project in pool.';
                    $content = 'This is an automated message. A new project has been assigned in your pool.';
                    $view = View::make('email/adminProject', ['admin_name' => $facultyData->first_name . ' ' . $facultyData->last_name, 'content' => $content]);
                    $mailMsg = $view->render();
                    send_mail($mailTo, $mailSubject, $mailMsg);
                }
                
                return redirect()->back()->with('success', 'Project has been assigned for the faculty pool and is available for them to request for use in their courses.');
            }
            return redirect()->back()->with('error', 'No projects were found int the pool.');
        } catch (Throwable $e) {
            report($e);
            return redirect()->back()->with('error', $e);
        }
    }


    /**
     * get the course from semester id. 
     * Uses ajax request on sem dropdown
     *
     * @param \Illuminate\Http\Request  $request
     * @return JSON
     */
    public function fetch_courses(Request $request)
    {
        if ($request->ajax()) 
        {
            $course_details =  $this->projectObj->getSemCourses($request->sem_id);
            if (!empty($course_details)) {
                foreach ($course_details as $count_course => $course_detail_arr) {
                    $team_arr[] = $course_detail_arr;
                }
            }
            //dd($team_arr);
            $return['listing'] = (!empty($team_arr)) ? $team_arr : [];
            return response()->json($return);
        }
    }

    /**
     * Fetch team from storage 
     * Uses ajax request on sem dropdown
     *
     * @param \Illuminate\Http\Request  $request
     * @return JSON
     */
    public function fetch_teamsbyId(Request $request)
    {
        if ($request->ajax()) {
            $teams_details =  $this->teamObj->getTeamsByProjectId($request->project_course_id);
            // dd($teams_details);
            if (!empty($teams_details)) {
                foreach ($teams_details as $count_team => $team_detail_arr) {
                    $team_arr[$team_detail_arr->course_name][] = $team_detail_arr;
                }
            }
            $return['listing'] = (!empty($team_arr)) ? $team_arr : [];

            return response()->json($return);
        }
    }

  
    /**
     * funtion to get heading for upload students
     * 
     * @param \Illuminate\Http\Request  $request
     * @return Json
     */
    public function getHeading(Request $request)
    {
        $user_id = auth()->user()->id;
        $courseData = $this->projectObj->getHeading($request->course_id);
        $heading = 'Upload Students to '.$courseData->semester.' '.$courseData->year.' - '.$courseData->prefix.' '.$courseData->number.' '.$courseData->section;
        
        return response()->json($heading);
    }

    /**
     * Change Status of project as archived from setting icon
     * 
     * @param \Illuminate\Http\Request  $request
     * @return json
     */
    public function makeArchive(Request $request)
    {
       $data = array(
            'status' => $request->status
        );
        $result = $this->projectObj->changeStatus($request->id, $data);
        if($result){
            $content = 'Project marked as Archived successfully.';
            return response()->json(array('response' => 'success', 'msg' => $content));
        }else{
            $content = 'Unable to make project as Archived.';
            return response()->json(array('response' => 'failed', 'msg' => $content));
        }
        
    }

    /**
     * Communication setting within a project
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function projectCourseSetting(Request $request)
    {
        $user =  Auth::user();
        //dd($user->university_users->id);
        $dataArray = $request->all();
        //dd($dataArray['communication_type']);
        foreach($dataArray['communication_type'] as $key => $communicationType){
            $insertArray[$key]['status'] = 0;
            if(isset($dataArray['status'])){
                foreach($dataArray['status'] as $key1 => $status){
                    if($key1 == $key){
                        $insertArray[$key]['status'] = $status;
                    }
                }                
            }else{
                $insertArray[$key]['status'] = 0;
            }
            $insertArray[$key]['communication_type'] = $communicationType;
            $insertArray[$key]['project_course_id'] = $dataArray['project_course_id'];
            $insertArray[$key]['created_by'] = $user->university_users->id;
            $insertArray[$key]['updated_by'] = $user->university_users->id;
            $insertArray[$key]['created_at'] = $dataArray['updated_at'];
            $insertArray[$key]['updated_at'] = $dataArray['updated_at'];
        }
        $result = $this->projectObj->projectCourseSetting($insertArray);
        $projectFullName = getProjectFullName($dataArray['project_course_id']);
        if($result){
            return redirect()->back()->with('success', 'Communication setting applyed for '.$projectFullName.' successfully.');
        }else{
            return redirect()->back()->with('error', 'Unable to process this request.');
        }
    }

    /**
     * Edit communication setting within a project
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function editProjectCourseSetting(Request $request)
    {
        $user =  Auth::user();
        $dataArray = $request->all();
        foreach($dataArray['id'] as $key => $value){
            $insertArray[$key]['status'] = 0;
            if(isset($dataArray['status'])){
                foreach($dataArray['status'] as $key1 => $status){
                    if($key1 == $dataArray['communication_type'][$key]){
                        $insertArray[$key]['status'] = $status;
                    }
                }                
            }else{
                $insertArray[$key]['status'] = 0;
            }
            $insertArray[$key]['id'] = $value;
            $insertArray[$key]['updated_by'] = $user->university_users->id;
            $insertArray[$key]['updated_at'] = $dataArray['updated_at'];
        }
        $result = $this->projectObj->editProjectCourseSetting($insertArray);
        $projectFullName = getProjectFullName($dataArray['project_course_id']);
        if($result){
            return redirect()->back()->with('success', 'Communication setting applyed for '.$projectFullName.' successfully.');
        }else{
            return redirect()->back()->with('error', 'Unable to process this request.');
        }
    }

    /**
     * Delete project course 
     * @param \Illuminate\Http\Request  $request
     * @return Json
     */
    public function deleteCourse(Request $request)
    {
       $data = array(
            'is_deleted' => $request->is_deleted
        );
        $isExist = $this->projectObj->isProjectCourseExist($request->id);
        //dd($isExist);
        if(!$isExist){
            $content = 'Course existing some where, You can not delete this.';
            return response()->json(array('response' => 'failed', 'msg' => $content));
        }else{
            $result = $this->projectObj->deleteCourse($request->id, $data);
            if($result){
                $content = 'Course deleted successfully.';
                return response()->json(array('response' => 'success', 'msg' => $content));
            }else{
                $content = 'Unable to delete course.';
                return response()->json(array('response' => 'failed', 'msg' => $content));
            }
        }
        
    }

    /**
     * Delete project course 
     * @param \Illuminate\Http\Request  $request
     * @return Array
     */
    public function getAssignCourses(Request $request)
    {
        $courseArray = [];
        $courseList = $this->projectObj->getCourseList($request->project_course_id);
        foreach($courseList->courses->semesters->courses as $key => $data){
            if($request->course_id == $data->id){
                $courseArray[$key]['id'] = $data->id;
                $courseArray[$key]['name'] = $data->prefix.' '.$data->number.' '.$data->section;
            }
            $isCourseAssign = $this->projectObj->isCourseAssign($data->id, $request->project_id);            
            if($isCourseAssign == 0){
                $courseArray[$key]['id'] = $data->id;
                $courseArray[$key]['name'] = $data->prefix.' '.$data->number.' '.$data->section;
            }
        }
        //dd($courseArray);
        return $courseArray;
    }

    /**
     * Delete project course 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function editProjectCourse(Request $request)
    {
        $data = $request->all();
        
        $dataArray['course_id'] = $data['course_id'];
        $dataArray['project_id'] = $data['project_id'];
        $dataArray['assigned_by'] = Auth::user()->university_users->id;
        $dataArray['assigned_at'] = date('Y-m-d H:i:s');
        $id = $data['id'];
        $result = $this->projectObj->editProjectCourse($id, $dataArray);
        if($result){
            return redirect()->back()->with('success', 'Project course updated successfully.');
        }else{
            return redirect()->back()->with('error', 'Unable to process this request.');
        }
    }

    /**
     * Add Change Request
     * 
     * @param \Illuminate\Http\Request  $request
     * @return Json
     */
    public function add_change_request_from_view(Request $request)
    {
        if ($request->ajax()) {
            $data = array(
                'title' => $request['title'],
                'project_course_id' => $request['project_course_id'],
                'description' => $request['description'],
                'status' => 0,
                'created_by' => Auth::user()->university_users->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            );
            $changeRequest =  $this->addChangeRequest($data);
            $cr= $changeRequest->id;
        
            if ($request->file('file') !== '' && !empty($request->file('file'))) {
                //get file mime type
                $fileType = '';
                $mime = $request->file('file')->getMimeType();
                $mimeType = explode('/', $mime);
                if($mimeType[0] == 'application'){
                    $file = explode('.', $mimeType[1]);
                    $fileType = array_pop($file);
                }else{
                    $fileType = $mimeType[0];
                }
                $upload_file = $this->uploadFileObj->uploadCRfile($request->file(), 'change-request', auth()->user()->id, $cr, 'change-request', $fileType);
            }
            if($changeRequest){
                return response()->json(['response'=>'success', 'msg'=>'Change request rdded successfully.']);
            }else{
                return response()->json(['response'=>'error', 'msg'=>'Unable to add change request.']);
            }
        }
    }

    /**
     * View Change Request
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function closed_change_request($id = null)
    {
        $user =  Auth::user();
        $user_profile_data = $this->userProfileObj->getUserProfile($user->id);
        //  || $user->role_id == Config::get('constants.roleTypes.faculty')|| $user->role_id == Config::get('constants.roleTypes.ta')
        if ($user->role_id == Config::get('constants.roleTypes.admin')) {
            if(isset($id)){
                $requests = $this->getClosedChangeRequests('', $id);
            }else{
                $requests = [];
            }
        } else {
            if(isset($id)){
                $requests = $this->getClosedChangeRequests($user_profile_data->university_users->id, $id);
            }else{
                $requests = [];
            }
        }
      
       
        $userUniverId = $this->userProfileObj->getUserUniversityID($user->id);
        $universityUserId = $userUniverId->id;
        
        $projects = $this->projectObj->getProjects($universityUserId, $user->role_id);        
        // dd($requests );

        $studentsList = $this->studentsList();

        return view('projects.change-requests.closed_change_request', ['requests' => $requests, 'user_profile_data' => $user_profile_data, 'projects' => $projects, 'studentsList' => $studentsList, 'universityUserId' => $universityUserId]);
    }

    /**
     * Check if pm plan already exist in storage
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function unique_pm_plan_name(Request $request)
    {
        $plan = $this->projectObj->unique_pm_plan_name($request); 
        return $plan;
    }

    /**
     * Check if client review template exist in storage
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uniqueClientReviewName(Request $request)
    {
        $result = $this->projectObj->uniqueClientReviewName($request); 
        return $result;
    }

    /**
     * Check if peer evaluation template exist in storage
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uniquePeerEvaluationName(Request $request)
    {
        $result = $this->projectObj->uniquePeerEvaluationName($request); 
        return $result;
    }
 
    /**
     * show project plan from storage
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function planDetails($id = null)
    {
        $planDetail = $this->projectObj->getProjectPlanDetails($id);
        //dd($planDetail);
        //return view('projects.plans.planDetail', compact('planDetail'));
        return response()->json($planDetail);
    }

    /**
     * get milestones from storage
     * @param \Illuminate\Http\Request  $request
     * @return Json
     */
    public function drowMilestoneTable(Request $request)
    {
        $milestones = $this->projectObj->getMilestones($request->plan_id);
        //dd($milestones);
        return response()->json(['listing'=>$milestones]);
    }

    /**
     * show media files from storage
     * @param
     * @return \Illuminate\Http\Response
     */
    public function mediaFiles()
    {
        $user_id = auth()->user()->id;
        $role_id = auth()->user()->role_id;
        $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
        $universityUserId = $user_profile_data->university_users->id;
        $mediaFiles = $this->projectObj->mediaFiles();
        return view('files.mediaFiles', compact('mediaFiles', 'user_profile_data', 'user_id', 'role_id'));
    }

    /**
     * add media file in files
     * @param \Illuminate\Http\Request  $request
     * @param $is_visible
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function addMediaFile(Request $request)
    {
        if ($request->file('file') !== '' && !empty($request->file('file'))) {
            //get file mime type
            $fileType = '';
            $mime = $request->file('file')->getMimeType();
            $mimeType = explode('/', $mime);
            if($mimeType[0] == 'application'){
                $file = explode('.', $mimeType[1]);
                $fileType = array_pop($file);
            }else{
                $fileType = $mimeType[0];
            }
            $upload_file = $this->uploadFileObj->uploadFile($request->file(), 'media', auth()->user()->id, auth()->user()->id, 'media_files', $fileType, false, $request['created_at'], $request['updated_at']);
            echo $upload_file;
        }
    }

    /**
     * update media files to visible to students from storage
     * @param int $file_id
     * @return \Illuminate\Http\Response
     */
    public function visibleToStudents($file_id, $is_visible)
    {
        // $i=0;
        // $data = [];
        // if(isset($request->is_visibleToStudents)){
        //     foreach ($request->is_visibleToStudents as $key => $value) {
        //         $data[] = $key;
        //         $i++;
        //     }
        // }
        $result = $this->projectObj->visibleToStudents($file_id, $is_visible);
        if($is_visible == 1){
            $message = 'Now selected files will be visible to students.';
        }else{
            $message = 'Now files will not visible to students.';
        }
        return redirect()->back()->with('success', '');
    }

    /**
     * get pm plan details
     * @param \Illuminate\Http\Request  $request
     * @return JSON
     */
    public function getPmPlanData(Request $request)
    {
        if ($request->ajax()) {
            
            $result = $this->projectObj->getPmPlanData($request->project_course_id);
            if($result){
                $status = $this->checkMilestoneActivity($result['milestone_id']);
                $result['status'] = $status;
                //dd($result);
            }
            return response()->json($result);
        }
    }

    /**
     * Edit Assigned Pm Plan to a project
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function editAssignedPmPlan(Request $request)
    {
        $milestones = $this->projectObj->getMilestones($request->plan_id);
        $teams = $this->projectObj->getTeamsByProjectCourseId($request->project_course_id);
        if (count($teams) == 0) {
            return redirect()->back()->with('error', 'No team assigned to this.');
        }
        $user_id = auth()->user()->id;
        $university_user = $this->teamObj->getUniversityUserId($user_id);
        if($request->edit_status){
            //delete existing milestone data
            $explodeMile = explode(',', $request->delete_milestone);
            foreach($explodeMile as $key => $expMile){
                foreach($teams as $key1 => $team){
                    $delData['milestone_id'] = $expMile;
                    $delData['team_id'] = $team->id;
                    $this->projectObj->deleteMilestone($delData);
                }                
            }
            //insert new selected pm plan date
            $user_id = auth()->user()->id;
            $university_user = $this->teamObj->getUniversityUserId($user_id);
            foreach ($milestones as $key => $milestone) {
                $updateDeadline = $this->projectObj->updateDeadline($milestone->id, $request['end_date'][$key]);
                foreach ($teams as $team) {
                    $arr = explode('-', $request['end_date'][$key]);
                    $newDate = $arr[2].'-'.$arr[0].'-'.$arr[1];
                    $data = array(
                        'milestone_id' => $milestone->id,
                        'team_id' => $team->id,
                        'status' => 1,
                        'end_date' => $newDate,
                        'created_by' => $university_user->id,
                        'created_at' => $request['updated_at'],
                        'updated_by' => $university_user->id,
                        'updated_at' => $request['updated_at'],
                    );
                    $assign_milestone = $this->projectObj->assignMilestone($data);
                    if ($assign_milestone) {
                        $this->projectObj->setPmPlanActive($request->plan_id, $request['updated_at']);
                    }
                }
            }
        }else{
            foreach ($milestones as $key => $milestone) {
                $updateDeadline = $this->projectObj->updateDeadline($milestone->id, $request['end_date'][$key]);
                foreach ($teams as $key1 => $team) {
                    $arr = explode('-', $request['end_date'][$key]);
                    $newDate = $arr[2].'-'.$arr[0].'-'.$arr[1];
                    $data = array(
                        'milestone_id' => $milestone->id,
                        'team_id' => $team->id,
                        'status' => 1,
                        'end_date' => $newDate,
                        'updated_by' => $university_user->id,
                        'updated_at' => $request['updated_at'],
                    );
                    $assign_milestone = $this->projectObj->updateAssignedMilestone($data);
                    if ($assign_milestone) {
                        $this->projectObj->setPmPlanActive($request->plan_id, $request['updated_at']);
                    }
                }
            }
        }
        return redirect()->back()->with('success', 'Course Plan updated successfully.');
    }
}

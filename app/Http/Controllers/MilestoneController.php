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
use App\Models\MilestoneProgress;
use App\Models\TeamStudent;
use Validator;
use App\Models\Categories;
use DB;
use App\Services\ProjectService;
use App\Services\UserProfileService;
use App\Services\UserService;
use App\Services\UploadFilesService;
use App\Services\TeamService;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Organization;

use App\Traits\DiscussionTrait;
use App\Traits\ProjectTrait;
use View;
use App\Models\Milestone;

class MilestoneController extends Controller
{
    use DiscussionTrait;
    use ProjectTrait; 
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth');
        $this->projectObj = new ProjectService();
        $this->userProfileObj = new UserProfileService(); // user profile Service object
        $this->uploadFileObj = new UploadFilesService(); // user profile Service object
        $this->userObj = new UserService();
        $this->teamObj= new TeamService();
    }

        /**
     * List of all milestone on the basis of get params
    * @param $project_id null
    * @param $team_id null
    * @param $milestone_id null
    * @param $type string file (file/discussion)
     * 
     * @return \Illuminate\Contracts\Support\Renderable
     */
    //     public function index($project_course_id = null, $team_id = null, $milestone_id=null, $type='file') {
    //         $milestones = $milestones_files= $team_arr= $discussions = [];
    //         try {
    //             $user_id = auth()->user()->id;
    //             $role_id = auth()->user()->role_id;
    //             $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
    //             $universityUserId = $user_profile_data->university_users->id;
    //             if(Auth::user()->role_id == 2){
    //                 $projects = $this->projectObj->getProjectsForClientMilestoneAndDiscussion();
    //             }else{
    //                 $projects = $this->projectObj->getProjects($universityUserId,$role_id);    
    //             }
    //             $teams_details = (!empty($project_course_id)) ? $this->projectObj->getProjectTeams($project_course_id, $universityUserId, $role_id) : [];

    //             if(!empty($teams_details))
    //             {
    //                 foreach ($teams_details as $count_team => $team_detail_arr){
    //                     $team_arr[$team_detail_arr->course_name][] = $team_detail_arr;
    //                 }
    //             }
    //             if(!empty($team_id))
    //             {   
    //                 if($role_id ==  1){
    //                     $milestones = $this->projectObj->getAllMilestones($team_id, $universityUserId=null);
    //                 }else{
    //                     $milestones = $this->projectObj->getAllMilestones($team_id, $universityUserId);
    //                 }
    //                 if(count($milestones)>0)
    //                 {
    //                     foreach ($milestones as $key => $milestone) 
    //                    {
    //                      $milestone_ids[] = $milestone->id;
    //                      $discussions[$milestone->id] = $this->getUserDiscussions($milestone->id,$universityUserId,'milestone_discussion');
    //                    }
    //                 $milestones_files = $this->projectObj->getMiletoneFiles($milestone_ids); 
    //                }
    //            }
    //         //   dd($milestones);
    //          return view('milestones.index', compact('user_profile_data', 'milestones', 'projects', 'project_course_id', 'team_id', 'team_arr', 'milestone_id', 'type', 'milestones_files', 'universityUserId','discussions', 'user_id','role_id'));

    //      } catch (Throwable $e) {
    //         report($e);
    //         return redirect()->route('projects')->with('error', $e);
    //     }
    // }

    /**
     * List of all milestone on the basis of get params
     * @param int $project_id null
     * @param int $team_id null
     * @param int $milestone_id null
     * @param int $type string file (file/discussion)
     * 
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index($project_course_id = null, $team_id = null, $milestone_id=null, $type='file') {
        $milestones = $milestones_files= $team_arr= $discussions = $milestonesData = [];
        try {
            $user_id = auth()->user()->id;
            $role_id = auth()->user()->role_id;
            $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
            $universityUserId = $user_profile_data->university_users->id;
            if(Auth::user()->role_id == 2){
                $projects = $this->projectObj->getProjectsForClient();
            }else{
                $projects = $this->projectObj->getProjects($universityUserId,$role_id);    
            }
            $teams_details = (!empty($project_course_id)) ? $this->projectObj->getProjectTeams($project_course_id, $universityUserId, $role_id, $isDeleted=2) : [];

            if(!empty($teams_details))
            {
                foreach ($teams_details as $count_team => $team_detail_arr){
                    $team_arr[$team_detail_arr->course_name][] = $team_detail_arr;
                }
            }
            if(!empty($team_id))
            {   
                $i= 0;
                if($role_id ==  1){
                    $allMilestone = $this->projectObj->getAllMilestones($team_id);
                    if(count($allMilestone)){
                        $milestonesData[$team_id] = $allMilestone;
                    }
                }else{
                    $allMilestone = $this->projectObj->getAllMilestones($team_id, $universityUserId);
                    if(count($allMilestone)){
                        $milestonesData[$team_id] = $allMilestone;
                    }
                }
                
             

                if(count($milestonesData)>0)
                {
                    foreach($milestonesData as $key => $mileData){
                        foreach ($mileData as $key1 => $milestone) {
                            $milestone_ids[] = $milestone->id;
                            $discussions[$milestone->id] = $this->getUserDiscussions($milestone->id,$user_id,'milestone_discussion');
                            $milestones[$key][$i]['data']=$milestone;
                            $milestones[$key][$i]['progress']= $this->projectObj->getMilestoneProgress($milestone->id, $milestone->project_milestone_id);
                            $i++;
                        }
                        $milestones_files = $this->projectObj->getMiletoneFiles($milestone_ids);
                    } 
                }

             
         }else{
            $teams = $this->teamObj->getTeamsByProjectId($project_course_id);
            foreach($teams as $key => $team){
                $allMilestone = $this->projectObj->getAllMilestones($team->team_id);
                if(count($allMilestone)){
                    $milestonesData[$team->team_id] = $allMilestone;
                }
            }
            //dd($milestonesData);
            $i= 0;
            foreach($milestonesData as $key => $mileData){
                //dump($mileData);
                
                foreach($mileData as $key1 => $milestone){
                    $milestone_ids[] = $milestone->id;
                    $discussions[$milestone->id] = $this->getUserDiscussions($milestone->id,$user_id,'milestone_discussion');
                    $milestones[$key][$i]['data']=$milestone;
                    $milestones[$key][$i]['progress']= $this->projectObj->getMilestoneProgress($milestone->id, $milestone->project_milestone_id);
                    $i++;
                }
                $milestones_files = $this->projectObj->getMiletoneFiles($milestone_ids); 
            }
            //dd($milestones);
         }
         return view('milestones.index', compact('user_profile_data', 'milestones', 'projects', 'project_course_id', 'team_id', 'team_arr', 'milestone_id', 'type', 'milestones_files', 'universityUserId','discussions', 'user_id','role_id'));

     } catch (Throwable $e) {
        report($e);
        return redirect()->route('projects')->with('error', $e);
    }
}

    /**
     * add attach ments in files on team id
     * @param  \Illuminate\Http\Request  $request
     * @param $team_id
     * @return \Illuminate\Http\Response
     */
    public function add_team_files(Request $request, $team_id) {
        try {
            if ( $request->file('file') !== '' && !empty($request->file('file')) ) {
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
                $upload_file = $this->uploadFileObj->uploadFile($request->file(), 'milestone_files', auth()->user()->id, $team_id, 'milestone_files', $fileType, false, $request['created_at'], $request['updated_at']);
                echo $upload_file;
            }

        } catch (Throwable $e) {
            report($e);
            return redirect()->route('allProject')->with('error', "error");
        }
    }



    /**
     * delete attachments in milestones
     * @param $file_id
     * @return \Illuminate\Http\Response
     */
    public function delete_files($file_id, $dateTime) {
        $user_id = auth()->user()->id;
        $teams_details =  $this->projectObj->deleteMilestoneFiles($file_id, $user_id, $dateTime);
        return redirect()->back()->with('success', 'File deleted successfully.');
    }

    /**
     * delete attachments in milestones
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function add_milestone_progress(Request $request)
    {
        if ($request->ajax()){
            if(isset($request->comment)){
                $user=Auth::user();
                // DB::enableQueryLog();
                $row=MilestoneProgress::where('milestone_id',$request->milestoneId)
                                    ->where('project_milestone_id',$request->projectMilestoneId)
                                    ->where('status',$request->status)
                                    ->where('created_by',$user->university_users->id)
                                    ->where('is_deleted', Config::get('constants.is_deleted.false'))->count();
                if($row<1)
                {
                    $data=array(
                    'milestone_id'=>$request->milestoneId,
                    'project_milestone_id'=>$request->projectMilestoneId,
                    'comment'=> $request->comment,
                    'status' => $request->status,
                    'created_by' =>$user->university_users->id,
                    'created_at' => $request->created_at,
                    );
                    $res=MilestoneProgress::insert($data);
                    // dd(DB::getQueryLog());
                    if($res && ($user->role_id == Config::get('constants.roleTypes.faculty') || $user->role_id == Config::get('constants.roleTypes.ta'))){
                        $mileData=Milestone::where('id',$request->milestoneId)->first();
                        if($user->role_id== Config::get('constants.roleTypes.faculty')) $userType="faculty";
                        if($user->role_id== Config::get('constants.roleTypes.ta')) $userType="ta";
                        $this->userObj->facutyCompleteMilestoneMailNotification($request->project_course_id, $mileData->name, $userType);
                    }
                    if($res)
                    {
                        if($user->role_id== Config::get('constants.roleTypes.student') || $user->role_id== Config::get('constants.roleTypes.client'))
                        {  if($request->project_course_id)
                          {
                            $mileData=Milestone::where('id',$request->milestoneId)->first();
                            if($user->role_id== Config::get('constants.roleTypes.student')) $userType="student";
                            if($user->role_id== Config::get('constants.roleTypes.client')) $userType="client";
                            $this->userObj->milestoneMailNotification($request->project_course_id, $userType, $mileData->name);
                          } 
                        } 
                        if($user->role_id== Config::get('constants.roleTypes.student')){
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
                                $upload_file = $this->uploadFileObj->uploadFile($request->file(), 'milestone_files', auth()->user()->id, $request->milestoneId, 'milestone_files', $fileType, 0, $request->created_at, $request->created_at);
                            }
                        }
                        $class="alert-success";
                        $message="Comment added successfully";
                        
                    }else{
                        $class="alert-danger";
                        $message="Data insert failed";
                        } 
                }else{
                    $class="alert-danger";
                    $message="You have already commented";
                }   
              }else{
                 $class="alert-danger";
                 $message="Please enter comment";
              }
              
              return response()->json(array('class'=>$class, 'message'=>$message));
            }
    }
   
    /**
     * delete attachments in milestones
     * @param \Illuminate\Http\Request  $request
     * @return boolean
     */
    public function isMilestoneExist(Request $request)
    {
        $count = $this->isMilestoneExists($request->milestone_id);
        if($count > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * update milestone date
     * @param \Illuminate\Http\Request  $request
     * @return boolean
     */
    public function changeMilestoneDate(Request $request)
    {
        //dd($request->all());
        $result = $this->projectObj->changeMilestoneDate($request);
                     
        if($result){
            return response()->json(array('status'=>true, 'milestone_id'=>$request->milestone_id));
        }else{
            return response()->json(array('status'=>false, 'milestone_id'=>$request->milestone_id));
        }
    }
}

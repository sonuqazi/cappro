<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\UserProfileService;
use App\Services\UserService;
use App\Services\ProjectService;
use App\Services\UploadFilesService;
use DB;
use App\Models\UserNotification;
use App\Traits\DiscussionTrait;
use App\Traits\TeamsTrait;
use App\Models\ProjectCourse;
use App\Models\ProjectCourseSetting;

class TeamDiscussionController extends Controller
{

     use DiscussionTrait;
     use TeamsTrait;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
     public function __construct() {
        $this->middleware('auth');

        $this->userProfileObj = new UserProfileService(); // user profile Service object
        $this->userObj=new UserService();
        $this->projectObj = new ProjectService();
        $this->uploadFileObj = new UploadFilesService(); // user profile Service object

    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($project_course_id = null, $team_id = null)
    { 
        $user_id = auth()->user()->id;
        $discussions= $team_arr= [];
        $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
        $universityUserId = $user_profile_data->university_users->id;
        if(Auth::user()->role_id == 2){
            $projects = $this->projectObj->getProjectsForClientMilestoneAndDiscussion();
        }else{
            $projects = $this->projectObj->getProjects($universityUserId,auth()->user()->role_id, 'discussion');
        }
        $teams_details = (!empty($project_course_id)) ? $this->projectObj->getProjectTeams($project_course_id, $universityUserId, auth()->user()->role_id, $isDeleted=2, 'discussion') : [];
        
        if(!empty($teams_details))
        {
            foreach ($teams_details as $count_team => $team_detail_arr){
                $team_arr[$team_detail_arr->course_name][] = $team_detail_arr;
            }
        }
       
        if(!empty($team_id))
         {
            $u_id =  auth()->user()->role_id != 1 ? $user_id : '';
            $discussions = $this->getUserDiscussions($team_id,$u_id,'team_discussion');
        }else{
            $u_id =  auth()->user()->role_id != 1 ? $user_id : '';
            $discussions = $this->getAllProjectDiscussions($project_course_id,$u_id,'team_discussion');
        }
   
        return view('discussions.index', ['user_profile_data' => $user_profile_data , 'projects' => $projects ,'discussions'=> $discussions, 'project_course_id'=> $project_course_id, 'team_id'=>$team_id, 'team_arr' => $team_arr ,'user_id' => $universityUserId]);
    }

    /**
     * Get project teams
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return Json
     */
    public function getTeams(Request $request)
    {
        // dd($request->id);
        $teams = $this->getProjectTeams($request->id, '',  '', $isDeleted=2);
        return response()->json(['status' => 201, 'response' => 'success','teams' => $teams]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user =  Auth::user();
        foreach($request['entity_id'] as $key => $entity_id){
            $data = array(
                'discussion_topic'=>  $request['discussion_topic'],
                'message' => $request['message'],
                'entity_id'=>  $entity_id,
                'entity_type'=> $request['entity_type'],        
                'created_by' => $user->university_users->id,
                'status' => 1,
                'created_at' => $request['created_at'],
                'updated_at' => $request['updated_at']
            );
            $discussion[] = $this->startDiscussion($data);
            
        }

        if( $request->file('file') !== '' && !empty($request->file('file'))) 
        {
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
            $upload_file = $this->uploadFileObj->uploadFile($request->file(), 'discussion', auth()->user()->id, array_values($discussion)[0]['id'], 'discussions', $fileType, false, $request['created_at'], $request['updated_at']);
        
            $fileData = $this->uploadFileObj->getFileData($upload_file);
            $insertValue = array_slice($discussion, 1);
            
            foreach($insertValue as $key => $value){
                $data = array(
                    'name' => $fileData['name'],
                    'entity_id' => $value['id'],
                    'entity_type' => $fileData['entity_type'],
                    'location' => $fileData['location'],
                    'description' => '',
                    'mime_type' => $fileData['mime_type'],
                    'created_by' => $fileData['created_by'],
                    'created_at' => $fileData['created_at'],
                    'updated_at' => $fileData['updated_at'],
                    'is_visibleToStudents' => $fileData['is_visibleToStudents']
                );
                $file[] = $this->addFile($data);
            }
        }
        if($discussion)
         {
           $result=$this->userObj->discussionMailNotificationSetting($request['project_course_id']);
         }

        if($request['entity_type'] == 'team_discussion')
        {
            if(count($request->entity_id) > 1 ){
                return redirect()->route('discussion',['project_course_id' => $request['project_course_id']])->with('success', 'Team Discussion created successfully.');
            }else{
                return redirect()->route('discussion',['project_course_id' => $request['project_course_id'], 'team_id'=> $request['entity_id'][0]])->with('success', 'Team Discussion created successfully.');
            }
        }else{
            // $redirect = $_SERVER['HTTP_REFERER'].'/'.$request['entity_id'].'/discussion';
            // return redirect()->to($redirect)->with('success', 'Discussion created successfully.');
            return redirect()->back()->with('success', 'Discussion created successfully.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, $dateTime)
    {
        $discussion = $this->deleteDiscussion($id, $dateTime);
        return redirect()->back()->with('success', 'Discussion deleted successfully.');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyComment($id, $dateTime)
    {
        $discussion = $this->deleteDiscussionComment($id, $dateTime);
        return redirect()->back()->with('success', 'Comment deleted successfully.');
    }
    
    /**
     * @description Send reply to discussion
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return Json
     */
    public function commentDiscussion(Request $request)
    {
        $user =  Auth::user();
        
        $data = array(
            'discussion_id' => $request['discussion_id'],
            'comment' => $request['comment'],     
            'created_by' => $user->university_users->id,
            'created_at' => $request['created_at'],
            'updated_at' => $request['created_at']
        );
        $discussion = $this->discussionComment($data);
        $discussionCommentCount = $this->discussionCommentCount($request['discussion_id']);

        $returnHTML = view('discussions.comment-reply')->with(['discussion' => $discussion, 'user' => $user])->render();

        return response()->json(['status' => 201, 'response' => 'success','returnHTML' => $returnHTML,'commentCount' => $discussionCommentCount]);

    }
}

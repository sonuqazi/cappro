<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Project;
use App\Models\ProjectCourse;
use App\Models\Team;
use App\Services\UserService;
use App\Services\ProjectService;
use App\Services\UploadFilesService;
use App\Services\UserProfileService;

use App\Traits\ProjectTrait;

class TaskController extends Controller
{
    use ProjectTrait;
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->projectObj = new ProjectService();
        $this->uploadFileObj = new UploadFilesService();
        $this->userProfileObj = new UserProfileService(); // user profile Service object
        $this->userObj= new UserService();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $taskId = $this->projectObj->addTask($request);
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
                $upload_file = $this->uploadFileObj->uploadTaskfile($request->file(), 'task_file', auth()->user()->id, $taskId, 'task_file', $fileType);
            }
            return redirect()->back()->with('success','Task created successfully.');
        } catch (Throwable $e) {
            report($e);
            return redirect()->back()->with('error', $e);
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
    public function destroy($id)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $project_course_id:$id
     * @return \Illuminate\Http\Response
     */
    public function tasks($project_course_id = null, $team_id = null)
    {
        try {
            $user_id = auth()->user()->id;
            $role_id = auth()->user()->role_id;
            $taskProjects = $taskChangeRequests = $team_arr = $tasks = [];
            $user = Auth::user();
            $user_profile_data = $this->userProfileObj->getUserProfile($user->id);
            $universityUserId = $this->userProfileObj->getUserUniversityID($user->id);

            $courses = $this->getallCourses();
            $projects = $this->projectObj->getProjects($universityUserId->id, $user->role_id);
            $teams_details = (!empty($project_course_id)) ? $this->projectObj->getProjectTeams($project_course_id, $universityUserId->id, $role_id, $isDeleted=2) : [];

            if(!empty($teams_details))
            {
                foreach ($teams_details as $count_team => $team_detail_arr){
                    $team_arr[$team_detail_arr->course_name][] = $team_detail_arr;
                }
            }
            //dd($team_arr);
            if($user->role_id == '2'){
                $createdBy = $user->university_users->id;
            }else{
                $createdBy = $user->university_users->id;
            }
            if($project_course_id != ''){
                $tasks = $this->projectObj->getTasks($createdBy, $user->role_id, $project_course_id, $team_id);
                foreach($tasks as $key => $task){
                    $taskProjects[]=$task->project;
                    $taskChangeRequests[]=$task->change_request;
                }
            }
             

            return view('projects.change-requests.tasks.index', ['requests' => $tasks, 'user_profile_data' => $user_profile_data, 'projects' => $projects, 'courses' => $courses, 'universityUserId' => $universityUserId->id, 'taskProjects' => $projects, 'taskChangeRequests' => array_unique($taskChangeRequests), 'team_arr' => $team_arr, 'project_course_id' => $project_course_id, 'team_id' => $team_id, 'user_id' => $createdBy]);
        } catch (Throwable $e) {
            report($e);
            return redirect()->back()->with('error', $e);
        }
    }

    /**
     * Mark task as completed the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function complete_task($id, $team_id)
    {
        $changeRequest = $this->completeTask($id, $team_id);
        if(isset($changeRequest))
        {
            $res=$this->userObj->mailSentCompleteTask($id, $team_id);
        }
        return redirect()->back()->with('success', 'Task marked as completed successfully!');
    }

    /**
     * Change the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function changeTaskOrder (Request $request)
    {
        try {
            $result = $this->projectObj->updateTaskOrder($request);
            return redirect()->back()->with('success', 'Task order updated successfully!');
        } catch (Throwable $e) {
            report($e);
            return redirect()->back()->with('error', $e);
        }
    }

    /**
     * Mark task as deleted the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteTask(Request $request)
    {
        $data = array(
            'updated_by' => Auth::user()->university_users->id,
            'updated_at' => $request->updated_at,
            'is_deleted' => 1
        );
        $result = $this->projectObj->deleteTask($request->id, $data);
        // if(isset($changeRequest))
        // {
        //     $res=$this->userObj->mailSentCompleteTask($request->id);
        // }
        return response()->json(array('status' => $result['status'], 'msg' => $result['msg']));
        //return redirect()->back()->with('success', 'Task deleted successfully!');
    }
}

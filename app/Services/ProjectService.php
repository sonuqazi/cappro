<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use App\Models\Team;
use App\Models\TeamStudent;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\MilestoneProgress;
use App\Models\UserProfile;
use App\Models\Course;
use App\Models\ProjectCourse;
use App\Models\Message;
use App\Models\UniversityUser;
use App\Models\ProjectRequest;
use App\Models\Task;
use App\Models\TeamTask;
use App\Models\ChangeRequest;
use App\Models\PeerEvaluation;
use App\Models\PeerEvaluationStart;
use App\Models\CourseStudent;
use App\Models\ProjectCourseSetting;
use App\Models\PeerEvaluationRatingStar;
use App\Models\EvaluationStart;
use App\Models\EvaluationQuestionStar;
use App\Models\ProjectMilestone;
use App\Models\TeamStudentCount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Services\MessageService;
use App\Models\Milestone;
use App\Models\PmPlan;
use App\Models\File;
use App\Models\Notification;
use App\Models\UserNotification;
use Illuminate\Support\Str;
use App\Mail\AdminUser;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
class ProjectService
{

    protected $projectRequest;
    public function __construct()
    {
        $this->projectRequest = new ProjectRequest();

        $this->messageObj = new MessageService(); // user message Service object
    }


    /**
     * @description fetch project by project course id
     * @param int $projectCourseId
     * @return object
     */
    public function fetchProjectByProjectCourseId($projectCourseId)
    {
      return ProjectCourse::select('project_id')->where('id','=',$projectCourseId)->first();
    }

    /**
     * Get project details using ID.
     * @param int $id
     * @return object|boolean
     */
    public function getProjectsDetails($id)
    {
        try {
            $projects = Project::with(['project_course' => function ($q) {
                $q->with(['teams' => function ($t) {
                    $t->with(['team_students'])->get();
                }])->get();
            }])->where('id', $id)->first();
            return $projects;
        } catch (Throwable $e) {

            return false;
        }
    }


    /**
     * Get project listing on projects page by user id
     * @param string $created_by
     * @return object
     */
    public function getProjectListings($created_by)
    {
        try {
            $projects = Project::with(['project_course' => function ($q) {
                $q->with(['teams' => function ($t) {
                    $t->with(['team_students'])->get();
                }])->get();
            }])->where('created_by', $created_by);
            return $projects;
        } catch (Throwable $e) {

            return false;
        }
    }



    /**
     * Get the project names and id, 
     * project created by auther
     * @param int $roleId, string $created_by
     * @return object
     */
    public function getProjects($created_by, $roleId = null, $type = null)
    {
        //   DB::enableQueryLog();
        $projects = $studentProjects = [];
        if ($roleId == '1') {
            $projectList = Project::select('projects.*')->with(['project_course'=>function($q){
                $q->where('project_courses.is_deleted', '=', 0);
                $q->with(['courses'=>function($q){
                    $q->with('semesters')->get();
                },])->get();
            }])->select('id', 'title', 'status')
            ->where('status', '2')->get();
        } else {
            if ($roleId == '2') {
                $projectList = Project::with(['project_course'=>function($q){
                    $q->where('project_courses.is_deleted', '=', 0);
                    $q->with(['courses'=>function($q){
                        $q->with('semesters')->get();
                    },])->get();
                }])->select('id', 'title', 'status')
                    ->where('status', '2')
                    ->where('client_id', $created_by)->get();
            }elseif($roleId == '4'){
                // $projectList = CourseStudent::select('course_students.*')
                // ->with(['project_courses.projects', 'project_courses.courses', 'project_courses'=>function($q){
                //     $q->where('project_courses.is_deleted', '=', 0);
                //     $q->with(['courses'=>function($q){
                //         $q->with('semesters')->get();
                //     },])->get();
                // }])->where('course_students.student_id', $created_by)->get();
                if($type == 'discussion'){
                    $projectList = ProjectCourse::select('project_courses.*', 'team_students.student_id')
                    ->with(['course_students', 'projects', 'courses'=>function($q){
                            $q->with('semesters')->get();
                    }, 'teams'=>function($q){
                        $q->with('team_students')->get();
                        $q->where('teams.is_deleted', '=', 0);
                    }])
                    ->join('course_students', 'course_students.course_id', '=', 'project_courses.course_id')
                    ->join('teams', 'teams.project_course_id', '=', 'project_courses.id')
                    ->join('team_students', 'team_students.team_id', '=', 'teams.id')
                    ->where('course_students.student_id', $created_by)
                    ->where('team_students.student_id', Auth::user()->university_users->id)
                    ->where('project_courses.is_deleted', 0)
                    ->where('team_students.is_deleted', 0)->get();
                }else{
                    $projectList = ProjectCourse::select('project_courses.*', 'team_students.student_id')
                    ->with(['course_students', 'projects', 'courses'=>function($q){
                            $q->with('semesters')->get();
                    }, 'teams'=>function($q){
                        $q->with('team_students')->get();
                        $q->where('teams.is_deleted', '=', 0);
                    }])
                    ->join('course_students', 'course_students.course_id', '=', 'project_courses.course_id')
                    ->join('teams', 'teams.project_course_id', '=', 'project_courses.id')
                    ->join('team_students', 'team_students.team_id', '=', 'teams.id')
                    ->where('course_students.student_id', $created_by)
                    ->where('team_students.student_id', Auth::user()->university_users->id)
                    ->where('project_courses.is_deleted', 0)
                    ->where('team_students.is_deleted', 0)->get();
                }
                //dd($projectList);
                if($type == 'message'){
                    foreach($projectList as $key => $projectCourse){
                        if(!empty($projectCourse) && !empty($projectCourse->teams)){
                            foreach($projectCourse->teams as $key1 => $teams){
                                $studentProjects[$projectCourse->id][$key1]['id'] = $projectCourse->id;
                                $studentProjects[$projectCourse->id][$key1]['project_id'] = $projectCourse->projects->id;
                                $studentProjects[$projectCourse->id][$key1]['title'] = $projectCourse->courses->semesters->semester.' '.$projectCourse->courses->semesters->year.'-'.$projectCourse->courses->prefix.' '.$projectCourse->courses->number.' '.$projectCourse->courses->section.'-'.$projectCourse->projects->title;
                                $studentProjects[$projectCourse->id][$key1]['status'] = $projectCourse->projects->status;
                                $studentProjects[$projectCourse->id][$key1]['sort'] = $projectCourse->courses->semesters->sort_code;
                                $studentProjects[$projectCourse->id][$key1]['team'] = $teams->name;
                                $studentProjects[$projectCourse->id][$key1]['team_id'] = $teams->id;
                                    
                            }
                        }
                    }
                }else{
                    foreach($projectList as $key => $projectCourse){
                        if(!empty($projectCourse) && !empty($projectCourse->teams)){
                            foreach($projectCourse->teams as $key1 => $teams){
                                foreach($teams->team_students as $key2 => $data){
                                    if($data->student_id == Auth::user()->university_users->id){
                                        $studentProjects[$projectCourse->id][$key1]['id'] = $projectCourse->id;
                                        $studentProjects[$projectCourse->id][$key1]['project_id'] = $projectCourse->projects->id;
                                        $studentProjects[$projectCourse->id][$key1]['title'] = $projectCourse->courses->semesters->semester.' '.$projectCourse->courses->semesters->year.'-'.$projectCourse->courses->prefix.' '.$projectCourse->courses->number.' '.$projectCourse->courses->section.'-'.$projectCourse->projects->title;
                                        $studentProjects[$projectCourse->id][$key1]['status'] = $projectCourse->projects->status;
                                        $studentProjects[$projectCourse->id][$key1]['sort'] = $projectCourse->courses->semesters->sort_code;
                                        $studentProjects[$projectCourse->id][$key1]['team'] = $teams->name;
                                        $studentProjects[$projectCourse->id][$key1]['team_id'] = $teams->id;
                                    }
                                }
                            }
                        }
                    }
                }
                //dd($studentProjects);
                foreach($studentProjects as $key => $projectWithTeams){
                    foreach($projectWithTeams as $key1 => $projectTeams){
                        $projects[] = $projectTeams;
                    }
                }
                $sort = array_column($projects, 'sort');
                array_multisort($sort, SORT_DESC, $projects);
                // dd($projects);
                return json_decode(json_encode($projects));
            }elseif($roleId == '3'){
                $projectList = ProjectCourse::with('courses.faculty_data.university_users', 'projects', 'courses.semesters')
                    ->Join('courses', 'courses.id', '=', 'project_courses.course_id')
                    ->Join('projects', 'projects.id', '=', 'project_courses.project_id')
                    ->select('project_courses.id as project_course_id', 'courses.id as course_id', 'projects.id as project_id')
                    ->where('projects.status', 2)
                    ->where('courses.faculty_id', $created_by)
                    ->where('project_courses.is_deleted', '0')->get();

                foreach($projectList as $key => $projectCourse){
                    if(!empty($projectCourse->project_course_id)){
                        $projects[$projectCourse->project_course_id]['id'] = $projectCourse->project_course_id;
                        $projects[$projectCourse->project_course_id]['title'] = $projectCourse->courses->semesters->semester.' '.$projectCourse->courses->semesters->year.'-'.$projectCourse->courses->prefix.' '.$projectCourse->courses->number.' '.$projectCourse->courses->section.'-'.$projectCourse->projects->title;
                        $projects[$projectCourse->project_course_id]['status'] = $projectCourse->projects->status;
                        $projects[$projectCourse->project_course_id]['sort'] = $projectCourse->courses->semesters->sort_code;
                    }
                }
                
                $sort = array_column($projects, 'sort');
                array_multisort($sort, SORT_DESC, $projects);
               
                return json_decode(json_encode($projects));
            }elseif($roleId == '5'){
                $projectList = ProjectCourse::with('courses.faculty_data.university_users', 'projects', 'courses.semesters')
                    ->Join('courses', 'courses.id', '=', 'project_courses.course_id')
                    ->Join('projects', 'projects.id', '=', 'project_courses.project_id')
                    ->select('project_courses.id as project_course_id', 'courses.id as course_id', 'projects.id as project_id')
                    ->where('projects.status', 2)
                    ->where('courses.ta_id', $created_by)
                    ->where('project_courses.is_deleted', '0')->get();

                foreach($projectList as $key => $projectCourse){
                    if(!empty($projectCourse->project_course_id)){
                        $projects[$projectCourse->project_course_id]['id'] = $projectCourse->project_course_id;
                        $projects[$projectCourse->project_course_id]['title'] = $projectCourse->courses->semesters->semester.' '.$projectCourse->courses->semesters->year.'-'.$projectCourse->courses->prefix.' '.$projectCourse->courses->number.' '.$projectCourse->courses->section.'-'.$projectCourse->projects->title;
                        $projects[$projectCourse->project_course_id]['status'] = $projectCourse->projects->status;
                        $projects[$projectCourse->project_course_id]['sort'] = $projectCourse->courses->semesters->sort_code;
                    }
                }
                 
                // dd($projects);
                $sort = array_column($projects, 'sort');
                array_multisort($sort, SORT_DESC, $projects);
                return json_decode(json_encode($projects));
            }
        }
        
        foreach($projectList as $projectArray){
            foreach($projectArray->project_course as $key => $projectCourse){
                $projects[$projectCourse->id]['id'] = $projectCourse->id;
                $projects[$projectCourse->id]['title'] = $projectCourse->courses->semesters->semester.' '.$projectCourse->courses->semesters->year.'-'.$projectCourse->courses->prefix.' '.$projectCourse->courses->number.' '.$projectCourse->courses->section.'-'.$projectArray->title;
                $projects[$projectCourse->id]['sort'] = $projectCourse->courses->semesters->sort_code;
            }
        }
        
        $sort = array_column($projects, 'sort');
        array_multisort($sort, SORT_DESC, $projects);
       
        $projects = json_decode(json_encode($projects));
        return $projects;
    }

    /**
     * Get the project teams using project ID, 
     * Join courses, teams and where teams created by auther
     * @param int project_course_id, date $created_by, int $roleId, boolean $isDeleted
     * @return object
     */
    public function getProjectTeams($project_course_id, $created_by, $roleId = null, $isDeleted=0, $type = null)
    {
        if ($roleId == '1') {
    
                //  DB::enableQueryLog();
              $query = Team::join('project_courses', 'project_courses.id', '=', 'teams.project_course_id')
              ->join('courses', 'courses.id', '=', 'project_courses.course_id')
              ->join('projects', 'projects.id', '=', 'project_courses.project_id')
              ->select('courses.prefix as course_name', 'courses.description', 'teams.name as team_name', 'teams.id as team_id','teams.is_deleted')
              ->where('teams.project_course_id', $project_course_id);
              if($isDeleted==0 || $isDeleted==1)
              {
                $query->where('teams.is_deleted', $isDeleted);
              }  
            $teams =$query->get();
            // dd(DB::getQueryLog());
            return $teams;
        }else{
            if ($roleId == '2') {
                $query = Team::join('project_courses', 'project_courses.id', '=', 'teams.project_course_id')
                ->join('courses', 'courses.id', '=', 'project_courses.course_id')
                ->join('projects', 'projects.id', '=', 'project_courses.project_id')
                ->select('courses.prefix as course_name', 'courses.description', 'teams.name as team_name', 'teams.id as team_id','teams.is_deleted')
                ->where('projects.client_id', $created_by)
                ->where('teams.project_course_id', $project_course_id);
                if($isDeleted==0 || $isDeleted==1)
                {
                    $query->where('teams.is_deleted', $isDeleted);
                }  
                $teams=$query->get();
                return $teams;
            }elseif ($roleId == '4') {
                if($type){
                    $query = Team::join('project_courses', 'project_courses.id', '=', 'teams.project_course_id')
                    ->join('courses', 'courses.id', '=', 'project_courses.course_id')
                    ->join('projects', 'projects.id', '=', 'project_courses.project_id')
                    ->join('team_students', 'team_students.team_id', '=', 'teams.id')
                    ->select('courses.prefix as course_name', 'courses.description', 'teams.name as team_name', 'teams.id as team_id','teams.is_deleted', 'team_students.student_id')
                    ->where('teams.project_course_id', $project_course_id)
                    ->where('team_students.student_id', Auth::user()->university_users->id);
                    if($isDeleted==0 || $isDeleted==1)
                    {
                        $query->where('teams.is_deleted', $isDeleted);
                    }  
                    $teams=$query->get();
                }else{
                    $query = Team::join('project_courses', 'project_courses.id', '=', 'teams.project_course_id')
                    ->join('courses', 'courses.id', '=', 'project_courses.course_id')
                    ->join('projects', 'projects.id', '=', 'project_courses.project_id')
                    ->select('courses.prefix as course_name', 'courses.description', 'teams.name as team_name', 'teams.id as team_id','teams.is_deleted')
                    ->where('teams.project_course_id', $project_course_id);
                    if($isDeleted==0 || $isDeleted==1)
                    {
                        $query->where('teams.is_deleted', $isDeleted);
                    }  
                    $teams=$query->get();
                }
                return $teams;
            }elseif ($roleId == '3'){
                $query = Team::join('project_courses', 'project_courses.id', '=', 'teams.project_course_id')
                ->join('courses', 'courses.id', '=', 'project_courses.course_id')
                ->join('projects', 'projects.id', '=', 'project_courses.project_id')
                ->select('courses.prefix as course_name', 'courses.description', 'teams.name as team_name', 'teams.id as team_id','teams.is_deleted')
                ->where('courses.faculty_id', $created_by)
                ->where('teams.project_course_id', $project_course_id);
                if($isDeleted==0 || $isDeleted==1)
                {
                  $query->where('teams.is_deleted', $isDeleted);
                }  
                $teams=$query->get();
                return $teams;
            }elseif ($roleId == '5'){
                   $query = Team::join('project_courses', 'project_courses.id', '=', 'teams.project_course_id')
                   ->join('courses', 'courses.id', '=', 'project_courses.course_id')
                   ->join('projects', 'projects.id', '=', 'project_courses.project_id')
                   ->select('courses.prefix as course_name', 'courses.description', 'teams.name as team_name', 'teams.id as team_id','teams.is_deleted')
                   ->where('courses.ta_id', $created_by)
                   ->where('teams.project_course_id', $project_course_id);
                   if($isDeleted==0 || $isDeleted==1)
                   {
                     $query->where('teams.is_deleted', $isDeleted);
                   }  
                    $teams=$query->get();
                return $teams;
            }
        }
    }

    /**
     * Get the project teams using project course ID, 
     * @param int project_course_id
     * @return object
     */
    public function getTeamsForFiles($project_course_id)
    {
            //  DB::enableQueryLog();
        $query = Team::select('teams.id as team_id')
        ->where('teams.project_course_id', $project_course_id)
        ->where('teams.is_deleted', 0);
        $teams =$query->get()->toArray();
        // dd(DB::getQueryLog());
        return $teams;
    }

    /**
     * Get the team students using team ID, 
     * Join university_users, users, roles and where team ID
     * @param int $team_id
     * @return object
     */
    public function getTeamStudents($team_id)
    {
        //dd($team_id);
        $teamStudent = TeamStudent::join('university_users', 'university_users.id', '=', 'team_students.student_id')
            ->join('users', 'users.id', '=', 'university_users.user_id')
            ->Join('roles', 'roles.id', '=', 'users.role_id')
            ->select('users.first_name as first_name', 'users.last_name as last_name', 'users.id as id', 'users.profile_image as profile_image', 'roles.name as role_name')
            ->where('team_students.team_id', $team_id)->get();
        return $teamStudent;
    }

    /**
     * Get the chat team students using team ID, 
     * @param int $team_id
     * @return object
     */
    public function getChatTeamStudents($team_id)
    {
        //dd($team_id);
        $teamStudent = TeamStudent::join('university_users', 'university_users.id', '=', 'team_students.student_id')
            ->join('users', 'users.id', '=', 'university_users.user_id')
            // ->leftJoin('messages', 'messages.sender_id', '=', 'users.id')
            // ->leftJoin('projects', 'projects.id', '=', 'messages.project_id')
            // ->leftJoin('teams', 'teams.id', '=', 'messages.team_id')
            ->Join('roles', 'roles.id', '=', 'users.role_id')
            //->select('users.first_name as first_name', 'users.last_name as last_name', 'users.id as id', 'users.profile_image as profile_image', 'roles.name as role_name', 'messages.id as msg_id', 'messages.sent_at as msg_date', 'messages.project_id', 'messages.team_id')
            ->select('users.first_name as first_name', 'users.last_name as last_name', 'university_users.id as id', 'users.profile_image as profile_image', 'roles.name as role_name')
            ->WhereIn('role_id', ['2', '3', '4', '5'])
            //->where('messages.receiver_id', auth()->user()->id)
            ->Where('users.status', '1')->Where('users.is_deleted', '0')
            //->where('team_students.team_id', $team_id)->orderBy('msg_date', 'DESC')->get();
            ->where('team_students.team_id', $team_id)
            ->where('team_students.is_deleted', '0')
            ->where('team_students.student_id', '!=', Auth::user()->university_users->id)->get();
        return $teamStudent;
    }

    /**
     * Get all files added in the team, Form files    
     * @param $team_id int
     * @return array
     */
    public function getTeamFiles($team_id)
    {
        return DB::table('files')->join('users', 'users.id', '=', 'files.created_by')
            ->join('teams', 'teams.id', '=', 'files.entity_id')
            ->select('files.id', 'files.name', 'files.created_by', 'files.created_at', 'files.description', 'users.first_name as first_name', 'users.last_name as last_name', 'profile_image', 'files.mime_type', 'users.role_id', 'files.is_shared', 'teams.name as teamName', 'teams.is_deleted as teamDeleted')
            ->where('entity_type', 'team_files')
            ->where('files.is_deleted', '0')
            ->where('entity_id', $team_id)->get();
    }

    /**
     * Get all files added in the team, Form files    
     * @param $team_id int
     * @return array
     */
    public function getProjectCourseFiles($projectCourseId)
    {
        $query = Team::select('teams.id as team_id')
        ->where('teams.project_course_id', $projectCourseId)
        ->where('teams.is_deleted', 0);
        $teams =$query->get()->toArray();
        
        return DB::table('files')->join('users', 'users.id', '=', 'files.created_by')
            ->join('teams', 'teams.id', '=', 'files.entity_id')
            ->select('files.id', 'files.name', 'files.created_by', 'files.created_at', 'files.description', 'users.first_name as first_name', 'users.last_name as last_name', 'profile_image', 'files.mime_type', 'users.role_id', 'files.is_shared', 'teams.name as teamName', 'teams.is_deleted as teamDeleted')
            ->where('entity_type', 'team_files')
            ->where('files.is_deleted', '0')
            ->whereIn('entity_id', $teams)->get();
    }

    /**
     * get project files    
     * @param int $project_id
     * @return array
     */
    public function getProjectsFiles($project_id)
    {
        return DB::table('files')->select('id', 'name', 'created_by', 'created_at', 'description')
            ->where('entity_type', 'project')
            ->where('is_deleted', '0')
            ->where('entity_id', $project_id)->get();
    }


    /**
     * delete the file on team folder, Cross check if same auther is making the request
     * @param $file_id int
     * @param $created_by int
     * @return bool
     */
    public function deleteTeamFiles($file_id, $created_by)
    {
        $delete = DB::table('files')->select('name')
            ->where('id', $file_id)
            ->where('created_by', $created_by)->get()->first();

        if (!empty($delete)) {
            DB::table('files')
                ->where('id', $file_id)
                ->update(['is_deleted' => '1']);
        }
        return;
    }

    /**
     * delete the file on team folder, Cross check if same auther is making the request
     * @param $file_id int
     * @param $created_by int
     * @return bool
     */
    public function deleteCRFile($file_id)
    {
        $delete = DB::table('files')
                ->where('id', $file_id)
                ->where('created_by', Auth::user()->id)
                ->update(['is_deleted' => '1']);
        if($delete){
            return true;
        }
        return false;
    }

    /**
     * share specific file with the client
     * @param $file_id int
     * @param $created_by int
     * @return bool
     */
    public function shareFileWithClient($file_id, $created_by)
    {
        $share = DB::table('files')->select('name', 'is_shared')
            ->where('id', $file_id)->get()->first();
        if (!empty($share)) {
            if($share->is_shared == 0){
                $isShare = 1;
            }else{
                $isShare = 0;
            }
            DB::table('files')
                ->where('id', $file_id)
                ->update(['is_shared' => $isShare]);
        }
        return $isShare;
    }


    /**
     * get the listing of added milestones from db
     * @param $team_id int
     * @param $created null
     * @return array
     */
    public function getAllMilestones($team_id, $created_by = null)
    {
        // DB::enableQueryLog();
        // $query=Milestone::select('milestones.id','milestones.name','milestones.description')->with('milestone_files','milestone_progress','project_milestone')
        // ->join('project_milestones', 'project_milestones.milestone_id', '=', 'milestones.id')
        // ->join('teams', 'teams.id', '=', 'project_milestones.team_id');
        // if($created_by!=null)
        // {
        //     $query->Where('milestones.created_by', $created_by);
        // }
        // $query->where('project_milestones.team_id', $team_id)
        // ->orderBy('milestones.order_counter', 'asc');
        // $result=$query->get();
        // dd($result);
        // dd(DB::getQueryLog()); 
        $query = DB::table('milestones')->select('milestones.id', 'name', 'description', 'project_milestones.created_by', 'milestones.deadline as end_date', 'project_milestones.team_id', 'project_milestones.id as project_milestone_id');
        $query->join('project_milestones', 'project_milestones.milestone_id', '=', 'milestones.id');
        $query->where('project_milestones.team_id', $team_id);

        $query->orderBy('order_counter', 'asc');
        $milestones = $query->get();
        return $milestones;
         
    }


    /**
     * get the files uploaded in the milestones
     * @param $milestone_ids arr
     * @return array
     */
    public function getMiletoneFiles($milestone_ids)
    {
        return File::with('mediaFileCreated_by')->select('name', 'id', 'entity_id', 'created_at', 'created_by')
            ->where('entity_type', 'milestone_files')
            ->where('is_deleted', 0)
            ->whereIn('entity_id', $milestone_ids)->get();
    }


    /**
     * delete the file on milestone folder, Cross check if same auther is making the request
     * @param $file_id int
     * @param $created_by int
     * @return bool
     */
    public function deleteMilestoneFiles($file_id, $created_by, $dateTime)
    {
        if(Auth::user()->role_id == 1){
            $delete = DB::table('files')->select('name')
            ->where('id', $file_id)->get()->first();
        }else{
            $delete = DB::table('files')->select('name')
            ->where('id', $file_id)
            ->where('created_by', $created_by)->get()->first();
        }

        if (!empty($delete)) {
            DB::table('files')->where('id', $file_id)->update(['is_deleted' => '1', 'updated_at' => $dateTime]);
            //unlink(public_path('projects/milestone_files/' . $delete->name));
        }
        return;
    }


    /**
     * get the full detailed details of project
     * @param $file_id int
     * @param $created_by int
     * @return bool
     */
    public function getProjectFullDetails()
    {
        $project_detail = DB::table('files')->select('name')
            ->where('id', $file_id)
            ->where('created_by', $created_by)->get()->first();
        return;
    }

    /**
     * update the file description
     * @param $file_id int
     * @param $description 
     * @return bool
     */
    public function save_edit_description($file_id, $description)
    {
        $affected = DB::table('files')
            ->where('id', $file_id)
            ->update(['description' => $description]);
        return;
    }


    /**
     * @description Function get count of filtered clients
     * @param object $request
     * @return Array
     */
    public function search_all_user_counts($request)
    {
        $keywords = $request->search['value'];
        $queries = DB::table('users')->select('users.id')
            //->where('users.status', '1')
            ->where('users.is_deleted', '0')
            ->where('users.role_id',  '!=', '2')
            ->join('roles', 'roles.id', '=', 'users.role_id');
        //->join('course_students', 'course_students.user_id', '=', 'users.id');
        if ($request->sem_id) {
            $queries->join('university_users', 'university_users.user_id', '=', 'users.id');
            $queries->join('course_students', 'course_students.student_id', '=', 'university_users.id');
            $queries->join('courses', 'courses.id', '=', 'course_students.course_id');
        }
        if (!empty($request->course_id)) {
            $queries->join('university_users', 'university_users.user_id', '=', 'users.id');
            $queries->join('course_students', 'course_students.student_id', '=', 'university_users.id');
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
        if ($request->sem_id) {
            $queries->Where('users.role_id', '4')
                ->Where('users.is_deleted', '0')
                ->Where('courses.semester_id', $request->sem_id);
                $queries->groupBy('users.id');
        }
        if (!empty($request->course_id)) {
            $queries->Where('users.role_id', '4')
                ->where('users.is_deleted', '0')
                ->Where('course_students.course_id', $request->course_id);
        }
        return $queries = $queries->get()->count();
        //return $queries->count();
    }

    /**
     * @access public
     * @description Function to get all university users
     * @param Array $request
     * @return Array
     */
    public function search_all_users($request)
    {
        //dd($request->course_id);
        $keywords = trim($request->search['value']);
        $start = $request->start;
        $limit = $request->length;
        $order_index = $request->order[0]['column'];
        $order_by = $request->columns[$order_index]['data'];
        $sort_by = $request->order[0]['dir'];
        $queries = DB::table('users')->select('user_name', 'first_name', 'last_name', 'email', 'roles.name as role_id', 'users.id', 'users.role_id as role_assigned')
            //->where('users.status', '1')
            ->where('users.is_deleted', '0')
            ->where('users.role_id',  '!=', '2')
            ->join('roles', 'roles.id', '=', 'users.role_id');
        if(Auth::user()->role_id==Config::get('constants.roleTypes.faculty'))
        { 
            $queries->where('users.role_id', Config::get('constants.roleTypes.student'));
        }
        //->join('course_students', 'course_students.user_id', '=', 'users.id');
        if ($request->sem_id) {
            $queries->join('university_users', 'university_users.user_id', '=', 'users.id');
            $queries->join('course_students', 'course_students.student_id', '=', 'university_users.id');
            $queries->join('courses', 'courses.id', '=', 'course_students.course_id');
        }
        if (!empty($request->course_id)) {
            $queries->join('university_users', 'university_users.user_id', '=', 'users.id');
            $queries->join('course_students', 'course_students.student_id', '=', 'university_users.id');
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
            $queries->Where('users.role_id', '4')
                ->Where('users.is_deleted', '0')
                ->Where('course_students.course_id', $request->course_id);
        }
        if ($request->sem_id) {
            $queries->Where('users.role_id', '4')
                ->Where('users.is_deleted', '0')
                ->Where('courses.semester_id', $request->sem_id);
                $queries->groupBy('users.id');
        }
        // return $queries = $queries->Where('users.role_id', '3')->orderBy($order_by, $sort_by)
        return $queries = $queries->orderBy($order_by, $sort_by)
            ->limit($limit, $start)
            ->get()->toArray();
    }

    /**
     * @access public
     * @description Function update
     * @param Array $request
     * @return Array
     */
    public function update_student_data($request, $user_id)
    {
        $update['first_name'] = $request->first_name;
        $update['last_name'] = $request->last_name;
        $update['role_id'] = $request->role_id;
        $update['user_name'] = $request->user_name;
        $update['email'] = $request->email;
        $update['updated_at'] = $request['updated_at'];
        if($request->email != $request->old_email){
            $update['status'] = 0;
            $update['email_verification_token'] = Str::random(32);
        }
        $user = User::find($request->id);
        $user->update($update);
        
        if($request->email != $request->old_email){
            Mail::to($request->email)->send(new AdminUser($user));
        }
        DB::table('model_has_roles')->where('model_id', $request->id)->delete();
        $role = Role::where('id', $request->role_id)->first();
        $user->assignRole($role['name']);
        return;
    }

    /**
     * view the list of added courses in semister
     * @param int $semester_id
     * @return object
     */
    public function getSemesterCourses($semester_id)
    {
        $query = DB::table('courses')->select('courses.id', 'prefix', 'number', 'courses.description', 'section', 'faculty_user.first_name as faculty_user', 'faculty_user.last_name as faculty_user_last_name', 'courses.created_at', 'courses.faculty_id', 'courses.ta_id', 'courses.semester_id', 'courses..student_count')
            ->leftJoin('university_users', 'university_users.id', '=', 'courses.faculty_id')
            ->leftJoin('users as faculty_user', 'faculty_user.id', '=', 'university_users.user_id')
            ->leftJoin('project_courses', 'project_courses.course_id', '=', 'courses.id')
            ->leftJoin('teams', 'teams.project_course_id', '=', 'project_courses.id')
            ->leftJoin('team_students', 'team_students.team_id', '=', 'teams.id');

        // if(Auth::user()->role_id != 1){
        //     $query->where('courses.created_by', Auth::user()->university_users->id);
        // }
        if(Auth::user()->role_id == 5){
            $query->where('courses.ta_id', Auth::user()->university_users->id);
        }
        if(Auth::user()->role_id == 3){
            $query->where('courses.faculty_id', Auth::user()->university_users->id);
        }
        if ($semester_id != null) {
            $query->where('courses.semester_id', $semester_id);
        }

        return $query->groupBy('courses.id')->orderBy('courses.id', 'asc')->get();
    }

    /**
     * get the list of all faculty users
     * 
     * @return object
     */
    public function getFacultyUsers()
    {
        return DB::table('users')->select('university_users.id', 'first_name', 'last_name', 'role_id', 'user_name')
            ->join('university_users', 'university_users.user_id', '=', 'users.id')
            ->WhereIn('role_id', ['3', '5']) // 5
            ->Where('status', '1')
            ->orderBy('first_name', 'desc')->get();
    }

    /**
     * get the list of all add semisters
     * @param 
     * @return object
     */
    public function getAllSemesters()
    {
        return DB::table('semesters')->select('id', 'semester', 'year')
            ->orderBy('sort_code', 'desc')->get();
    }

    /**
     * get the current semister
     * @param 
     * @return object
     */
    public function getCurrentSemester()
    {
        return DB::table('semesters')->select('id', 'semester', 'year')->where('year', date('Y'))
            //->where('semester' , 'Spring')
            ->first();
    }

    /**
     * get the list of all faculty users
     * @param $user_id auth id string
     * @param 
     * @return object
     */
    public function isCourseNumberVAlid($request)
    {
        // DB::enableQueryLog();
        $course= Course::select('id')
            ->Where(['prefix'=>$request->prefix, 'number' => $request->number, 'section'=> $request->section, 'semester_id'=> $request->semester_id])
            ->first();
        // dd(DB::getQueryLog());
        return $course;
    }

    /**
     * save a new course to db
     * @param object $request, int $user_id
     * @return
     */
    public function addNewCourse($request, $user_id)
    {
        $course = array(
            'prefix' => $request->prefix,
            'description' => $request->description,
            'number' => $request->number,
            'section' => $request->section,
            'faculty_id' => $request->faculty_id,
            'semester_id' => $request->semester_id,
            'ta_id' => $request->ta_id,
        );
        if(Auth::user()->role_id == 3){
            $course['faculty_id'] = Auth::user()->university_users->id;
        }
        if(Auth::user()->role_id == 5){
            $course['ta_id'] = Auth::user()->university_users->id;
        }
        if (!empty($request->id)) {
            $course['updated_at'] = $request->updated_at;
            DB::table('courses')
                ->where('id', $request->id)
                ->update($course);
        } else {
            $course['updated_at'] = $request->updated_at;
            $course['created_at'] = $request->created_at;
            $total_course = DB::table('courses')->Where('semester_id', $request->semester_id)->count();
            DB::table('semesters')->where('id', $request->semester_id)->update(['total_courses' => $total_course]);
            $university_user =  DB::table('university_users')->select('id')
                ->Where('user_id', $user_id)->first();
            $course['created_by'] = $university_user->id;
            DB::table('courses')->insert($course);
        }
        return;
    }


    /**
     * @access public
     * @desc Function to get all plan planns
     * @param object $request
     * @return Array
     */
    public function search_all_plans($request)
    {
        $keywords = trim($request->search['value']);
        $start = $request->start;
        $limit = $request->length;
        $order_index = $request->order[0]['column'];
        $order_by = $request->columns[$order_index]['data'];
        $sort_by = $request->order[0]['dir'];
        $queries = PmPlan::with('usercreated_by.university_users', 'userupdated_by.university_users')->select('pm_plans.name', 'pm_plans.id', 'pm_plans.description', 'pm_plans.milestone_count', 'pm_plans.is_active', 'pm_plans.is_deleted', 'pm_plans.created_by', 'pm_plans.created_at', 'pm_plans.updated_by', 'pm_plans.updated_at');

        if ($order_by == 'pm_plans_name') {
            $order_by = 'pm_plans.name';
        } elseif ($order_by == 'pm_plans_description') {
            $order_by = 'pm_plans.description';
        }

        $keywords = trim($keywords);
        if (!empty($keywords)) {
            $search_words =  explode(' ', $keywords);
            $firstword = $search_words[0];
            $lastword = @$search_words[1];

            if (empty($lastword)) {
                $queries->Where(function ($query) use ($firstword) {
                    $query->where('pm_plans.name', 'like', '%' . $firstword . '%')
                        ->orWhere('pm_plans.description', 'like', '%' . $firstword . '%');
                });
            } else {
                $queries->Where(function ($query) use ($firstword, $lastword) {
                    $query->where('pm_plans.name', 'like', '%' . $firstword . '%')
                        ->Where('pm_plans.name', 'like', '%' . $lastword . '%')
                        ->orWhere('pm_plans.description', 'like', '%' . $firstword . ' ' . $lastword . '%')
                        ->orWhere('pm_plans.description', 'like', '%' . $firstword . ' ' . $lastword . '%');
                });
            }
        }
        return $queries = $queries->where('pm_plans.is_deleted', '0')->groupBy('pm_plans.id')->orderBy($order_by, $sort_by)
            ->limit($limit, $start)
            ->get();
    }

    /**
     * @description Function get count of filtered pm plans
     * @param object $request
     * @return Array
     */
    public function search_all_plans_counts($request)
    {
        $keywords = $request->search['value'];
        $queries = DB::table('pm_plans')->select('milestones.id');

        $keywords = trim($keywords);
        if (!empty($keywords)) {
            $search_words =  explode(' ', $keywords);
            $firstword = $search_words[0];
            $lastword = @$search_words[1];

            if (empty($lastword)) {
                $queries->Where(function ($query) use ($firstword) {
                    $query->where('pm_plans.name', 'like', '%' . $firstword . '%')
                        ->orWhere('pm_plans.description', 'like', '%' . $firstword . '%');
                });
            } else {
                $queries->Where(function ($query) use ($firstword, $lastword) {
                    $query->where('pm_plans.name', 'like', '%' . $firstword . '%')
                        ->Where('pm_plans.name', 'like', '%' . $lastword . '%')
                        ->orWhere('pm_plans.description', 'like', '%' . $firstword . ' ' . $lastword . '%')
                        ->orWhere('pm_plans.description', 'like', '%' . $firstword . ' ' . $lastword . '%');
                });
            }
        }


        return $queries->where('pm_plans.is_deleted', '0')->count();
    }


    /**
     * save a new course to db
     * @param object $request 
     * @return object
     */
    public function update_pm_plans($request, $user_id)
    {
        $plan = array(
            'name' => $request->name,
            'description' => $request->description,
        );
        if (!empty($request->id)) {
            $plan['updated_by'] = Auth::user()->university_users->id;
            $plan['updated_at'] = $request->updated_at;
            DB::table('pm_plans')
                ->where('id', $request->id)
                ->update($plan);

            $plan_id = $request->id;
            $msg = 'Course Plan updated successfully.';
        } else {
            $plan['created_by'] = Auth::user()->university_users->id;
            $plan['created_at'] = $request->created_at;
            $plan['updated_at'] = $request->updated_at;
            DB::table('pm_plans')->insert($plan);
            $msg = 'Course Plan saved successfully';
            $plan_id = DB::getPdo()->lastInsertId();
        }

        if (!empty($request->milestone_name)) {
            $add_milestone_name_arr = $request->milestone_name;
            $add_milestone_description_arr = $request->milestone_description;
            $add_milestone_deadline_arr = $request->deadline;
            $add_milestone_id_arr = $request->milestone_id;

            $count_new_milestones = count($add_milestone_name_arr);
            $order_counter = 1;
            foreach ($add_milestone_name_arr as $count_milestone => $single_milestone_name) {
                if (empty($add_milestone_id_arr[$count_milestone])) {
                    $insert_milestone['name'] = $single_milestone_name;
                    $insert_milestone['description'] = $add_milestone_description_arr[$count_milestone];
                    $insert_milestone['plan_id'] = $plan_id;
                    $insert_milestone['deadline'] = Carbon::createFromFormat('m-d-Y', $add_milestone_deadline_arr[$count_milestone])->format('Y-m-d');
                    $insert_milestone['created_by'] = Auth::user()->university_users->id;
                    $insert_milestone['created_at'] = date('Y-m-d H:i:s');
                    $insert_milestone['order_counter'] = $order_counter;

                    $result = DB::table('milestones')->insert($insert_milestone);
                    $newMileId = DB::getPdo()->lastInsertId();
                    //code will be here
                    if(isset($add_milestone_id_arr)){
                        $milestoneExist = ProjectMilestone::where('milestone_id', $add_milestone_id_arr['1'])->get();
                        //dd($milestoneExist);
                        if(count($milestoneExist) > 0){
                            foreach($milestoneExist as $exi => $exist){
                                $assignMilestone['milestone_id'] = $newMileId;
                                $assignMilestone['team_id'] = $exist->team_id;
                                $assignMilestone['status'] = 1;
                                $assignMilestone['created_by'] = Auth::user()->university_users->id;
                                $assignMilestone['created_at'] = date('Y-m-d H:i:s');
                                $assignMilestone['updated_by'] = Auth::user()->university_users->id;
                                $assignMilestone['updated_at'] = date('Y-m-d H:i:s');
                                //dd($assignMilestone);
                                $result = ProjectMilestone::insert($assignMilestone);
                            }
                        }
                    }
                } else {
                    $update_milestone['name'] = $single_milestone_name;
                    $update_milestone['description'] = $add_milestone_description_arr[$count_milestone];
                    $update_milestone['deadline'] = Carbon::createFromFormat('m-d-Y', $add_milestone_deadline_arr[$count_milestone])->format('Y-m-d');
                    $update_milestone['updated_by'] = Auth::user()->university_users->id;
                    $update_milestone['updated_at'] = date('Y-m-d H:i:s');
                    $update_milestone['order_counter'] = $order_counter;

                    DB::table('milestones')
                        ->where('id', $add_milestone_id_arr[$count_milestone])
                        ->update($update_milestone);
                }
                //update milestone counts
                $update_milestone_count['milestone_count'] = $count_new_milestones;
                DB::table('pm_plans')
                    ->where('id', $plan_id)
                    ->update($update_milestone_count);
                $order_counter++;
            }
            //delete selected mile stones
            $expDelMilData = explode(',', $request->milestone_deleted);
            $realData = array_unique(array_filter($expDelMilData));        
            foreach($realData as $key => $delMilData){
                DB::table('milestones')
                    ->where('id', $delMilData)
                    ->update(['is_deleted' => 1]);
            }
        }
        return $msg;
    }


    /**
     * fetch the milestones saved ina pm plans
     * @param object $request
     * @return
     */
    public function get_all_milestones_with_plans($request)
    {
        return Milestone::with('project_milestone')->select('id', 'name', 'description', 'deadline')
            ->Where('plan_id', $request->plan_id)
            ->Where('is_deleted', '0')
            ->orderBy('order_counter', 'asc')->get();
    }

    /**
     * fetch other details for project
     * @param int $project_id
     * @return object
     */
    public function getProjectOtherDetails($project_id)
    {
        return DB::table('categories')->select('categories.id', 'title')
            ->leftJoin('project_categories', 'project_categories.category_id', '=', 'categories.id')
            ->Where('project_id', $project_id)
            ->get();
    }

    /**
     * fetch other details for project
     * @param int $project_id
     * @return object
     */
    public function getProjectClientDetails($project_id)
    {
        return DB::table('users')->select('first_name', 'last_name', 'organizations.name as organization')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->join('university_users', 'university_users.user_id', '=', 'users.id')
            ->join('projects', 'projects.client_id', '=', 'university_users.id')
            ->leftJoin('organizations', 'organizations.id', '=', 'user_profiles.org_id')
            ->Where('projects.id', $project_id)
            ->first();
    }

    /**
     * fetch proejct categories
     * @param int $project_id
     * @return object
     */

    public function getProjectCategories()
    {
        return DB::table('categories')->select('id', 'title')
            ->where('is_active', 1)->where('is_deleted', 0)
            ->get();
    }


    /**
     * add proejct categories
     * @param $categories_id int
     * @param $project_id int
     * @return
     */
    public function save_project_category($categories_id, $project_id, $createdAt, $updateAt)
    {
        $insert_category['project_id'] = $project_id;
        $university_user = DB::table('university_users')->select('id')
            ->Where('user_id', auth()->user()->id)->first();
        $created_by = $university_user->id;

        foreach ($categories_id as $key => $category) {
            $insert_category['category_id'] = $category;
            $insert_category['added_by'] = $created_by;
            $insert_category['added_at'] = $createdAt;
            $insert_category['created_at'] = $createdAt;
            $insert_category['updated_at'] = $updateAt;
            DB::table('project_categories')->insert($insert_category);
        }
    }

    /**
     * @description Function get count of filtered clients
     * @param object $request
     * @return Array
     */
    public function search_all_client_counts($request)
    {
        
        if(!empty($request->semId)){
            $keywords = $request->search['value'];
            $queries = DB::table('users')->select('users.id')
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
                            ->orWhere('organizations.name', 'like', '%' . $firstword . '%');
                    });
                }
            }
        }else{
        
            $keywords = $request->search['value'];
            $queries = DB::table('users')->select('users.id')
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
                            ->orWhere('organizations.name', 'like', '%' . $firstword . '%');
                    });
                }
            }

            if (!empty($request->course_id)) {
                $queries->Where('users.role_id', '4');
            }
        }
        if(!empty($request->semId)){
            return count($queries->get()->toArray());
        }else{
            return $queries->count();
        }
    }

    /**
     * Get all projects according to status
     * @param sting $status, string $category, string $date, boolean $isAssigned 
     * @retun Array
     */

    public function getAllProjects($status, $category, $sort, $date, $isAssigned = null, $semId = null)
    {

        
        if ($sort == 'old-new') {
            $name = 'created_at';
            $type = 'asc';
        } else if ($sort == 'project-A-Z') {
            $name = 'title';
            $type = 'asc';
        } else if ($sort == 'project-Z-A') {
            $name = 'title';
            $type = 'desc';
        } else if ($sort == 'client-A-Z') {
            $name = '';
            $type = '';
        } else if ($sort == 'client-Z-A') {
            $name = '';
            $type = '';
        } else {
            $name = 'created_at';
            $type = 'desc';
        }
        $query =  Project::select('projects.*')->with(['project_categories', 'project_client.university_users', 'usercreated_by'=>function($q){
            $q->with(['university_users'=>function($q){
                $q->with('user_profiles')->get();
            },])->get();
        }, 'project_course'=>function($q){
            $q->with('project_course_setting', 'courses')->get();
            if(isset($_GET['tab'])){
                if($_GET['tab'] == 'active'){
                    $q->where('project_courses.is_deleted', '=', 0);
                }
            }
        }, 'project_requests'])
            ->whereHas('project_categories', function ($q) use ($category) {
                // Query the name field in status table
                if ($category != '') {
                    $q->where('category_id', '=', $category);
                }
            });
        if($status != 5){
            $query->where('projects.status', $status);
        }
        if ($semId != null) {
            $query->Join('project_courses', 'project_courses.project_id', 'projects.id');
            $query->Join('courses', 'courses.id', 'project_courses.course_id');
            $query->where('courses.semester_id', $semId);
            $query->where('project_courses.status', $status);
        }
        if ($status == 1) {
            // $query->select('projects.*','project_courses.id as project_course_id');
            // $query->leftJoin('project_courses', 'project_courses.project_id', '=', 'projects.id');
            // $query->orWhere('project_courses.status', '=', 2);
            $query->select('projects.*');
            $query->orWhere('projects.status', '=', 2);
            $query->whereHas('project_categories', function ($q) use ($category) {
                // Query the name field in status table
                if ($category != '') {
                    $q->where('category_id', '=', $category);
                }
            });
        }
        if ($status == 5) {
            $query->select('projects.*','project_courses.id as project_course_id', 'project_courses.status as completed', 'project_courses.is_deleted as courseDeleted');
            $query->leftJoin('project_courses', 'project_courses.project_id', '=', 'projects.id');
            $query->Where('project_courses.status', '=', 5);
        }
        
        if ($isAssigned != '') {
            $query->where('is_assigned', '=', $isAssigned);
            //$query->whereNotIn('project_requests.project_id', 'projects.id');
        }
        if ($date != '') {
            $query->whereDate('created_at', '=', $date);
        }
        if ($name != '' && $type != '') {
            $query->orderBy($name, $type);
        }
        if ($sort == 'client-A-Z') {
            $projects =   $query->get()->sortBy(function ($q) {
                return $q->usercreated_by->first_name;
            })->all();
        } else if ($sort == 'client-Z-A') {
            $projects =   $query->get()->sortByDesc(function ($q) {
                return $q->usercreated_by->first_name;
            })->all();
        } else {
            $projects =   $query->get();
        }
        
        return $projects;
    }



        /**
     * Get all projects according to status
     * @param sting $status, string $category, string $date, boolean $isAssigned 
     * @retun Array
     */

    public function fetchAllProjects($status, $category, $sort, $date, $university_userId, $role, $isAssigned = null)
    {
        // DB::enableQueryLog(); // Enable query log

        
         
        $query =  Project::select('projects.*')->with(['project_categories', 'project_client.university_users', 'usercreated_by'=>function($q){
            $q->with(['university_users'=>function($q){
                $q->with('user_profiles')->get();
            },])->get();
        }, 'project_course'=>function($q){
            $q->with('project_course_setting', 'courses')->get();
            if(isset($_GET['tab'])){
                if($_GET['tab'] == 'active'){
                    $q->where('project_courses.is_deleted', '=', 0);
                }
            }
        }, 'project_requests'])
            ->whereHas('project_categories', function ($q) use ($category) {
                // Query the name field in status table
                if ($category != '') {
                    $q->where('category_id', '=', $category);
                }
            });
      
        if($status != 5){
            $query->where('projects.status', $status);
        }
        if ($status == 1) {
            $query->orWhere('projects.status', '=', 2);
           
        }
        if ($status == 5) {
            $query->select('projects.*','project_courses.id as project_course_id', 'project_courses.status as completed', 'project_courses.is_deleted as courseDeleted');
            $query->leftJoin('project_courses', 'project_courses.project_id', '=', 'projects.id');
            $query->Where('project_courses.status', '=', 5);
        }
        
        if ($isAssigned != '') {
            $query->where('is_assigned', '=', $isAssigned);
            //$query->whereNotIn('project_requests.project_id', 'projects.id');
        }

        if($role==2)
        {
            $query->where('projects.client_id', '=', $university_userId);
        }elseif($role==4)
        {
        $query->join('project_courses as pc', 'pc.project_id', 'projects.id');
        $query->join('course_students', 'course_students.course_id', '=', 'pc.course_id');
        $query->where('course_students.student_id', $university_userId);
        $query->where('pc.is_deleted', 0);
        }

        
            $projects =   $query->get();
        
        //  dd(DB::getQueryLog()); // Show results of log

        return $projects;
    }
    /**
     * Update project status
     * @param int $id, array $data 
     * @return Array
     */

    public function changeStatus($id, $data, $projectCourseId = null)
    {
        $status =  Project::where('id', $id)->update($data);
        if($data['status'] == 5){
            $status =  Project::where('id', $id)->update($data);
        }
        if(isset($projectCourseId)){
            $data = array(
                'status' => $data['status']
            );
           $result = ProjectCourse::where('id', $projectCourseId)->update($data);
        }
        return $status;
    }

    /* delete the pm plan
     * @param int $plan_id
     * @return bool
     */
    public function delete_pm_plan($plan_id, $dateTime)
    {
        $delete = DB::table('pm_plans')->select('name')
            ->where('id', $plan_id)->get()->first();
        if (!empty($delete)) {
            $plan['is_deleted'] = '1';
            $plan['updated_at'] = $dateTime;
            DB::table('pm_plans')
                ->where('id', $plan_id)
                ->update($plan);
        }
        return;
    }

    /**
     * Get the semester teams using semester ID, 
     * @param int $sem_id
     * @return void
     */
    public function getCourses($sem_id)
    {

        $teams = Course::where('semester_id', $sem_id)->get()->toArray();
        return $teams;
    }

    /**
     * Get the semester teams using semester ID, 
     * Join courses, teams and where teams created by auther
     * @param object $request
     * @return void
     */
    public function assignCourses($request)
    {
        // dd($request);
        $university_user =  DB::table('university_users')->select('id')
            ->Where('user_id', auth()->user()->id)->first();
        foreach ($request['courses_id'] as $course) {
            $data = array(
                'course_id' => $course,
                'project_id' => $request['project_id'],
                'assigned_by' => $university_user->id,
                'assigned_at' => $request['updated_at']
            );

            $course =   ProjectCourse::create($data);
        }

        $project = Project::find($request['project_id']);
        $project->update(['status' => 2]);
        return true;
    }

    /** Get the team members using team ID, 
     * Join university_users, users, roles and where team ID
     * @param int $team_id
     * @return void
     */
    public function getTeamMembers($team_id)
    {
        //dd($team_id);
        $teamStudent = TeamStudent::join('university_users', 'university_users.id', '=', 'team_students.student_id')
            ->join('users', 'users.id', '=', 'university_users.user_id')
            ->Join('roles', 'roles.id', '=', 'users.role_id')
            ->select('users.first_name as first_name', 'users.last_name as last_name', 'users.id as id', 'users.profile_image as profile_image', 'roles.name as role_name', 'roles.id as role_id', 'university_users.id as uniUserId')
            ->WhereIn('role_id', ['3', '4', '5'])
            ->where('team_students.team_id', $team_id)
            ->where('team_students.is_deleted', '0')->get();
        return $teamStudent;
    }

 /** Get the team members using team ID, 
     * Join university_users, users, roles and where team ID
     * @param int $team_id
     * @return object
     */
    public function getFacultyTaByTeamId($teamId)
    {
        $data=Team::select('fa.id as fa_user_id','ta.id as ta_user_id','fauser.profile_image as fprofile_image','tauser.profile_image as tprofile_image', 'fauser.first_name as fa_first_name', 'fauser.last_name as fa_last_name','fauser.email as fa_email','tauser.email as ta_email','tauser.first_name as ta_first_name','tauser.last_name as ta_last_name')
        ->join('project_courses', 'project_courses.id', 'teams.project_course_id')
        ->join('courses', 'courses.id', 'project_courses.course_id')
        ->leftjoin('university_users as fa', 'fa.id', 'courses.faculty_id')
        ->leftjoin('university_users as ta', 'ta.id', 'courses.ta_id')
        ->leftjoin('users as fauser', 'fauser.id', 'fa.user_id')
        ->leftjoin('users as tauser', 'tauser.id', 'ta.user_id')
        ->where('teams.id',$teamId)->get();
        return $data;
    }


     /** Get all active project
        * @return object
         */
    public function getAllActiveProjects($semester_id)
    {
        // try {
        //     $projects = Project::with(['project_categories', 'usercreated_by', 'project_course'=>function($q){
        //         $q->with(['courses'=>function($q){
        //             $q->with(['faculty_data'=>function($q){
        //                 $q->with(['university_users'])->get();
        //             },])->get();
        //         },])->get();
        //     }, 'usercreated_by'=>function($q){
        //     $q->with(['university_users'=>function($q){
        //         $q->with('user_profiles')->get();
        //     },])->get();
        // }])
        //     ->Join('project_courses', 'project_courses.project_id', '=', 'projects.id')
        //     ->join('courses', 'courses.id', 'project_courses.course_id')
        //     ->select('projects.*')
        //         ->where('projects.status', '2')->where('project_courses.is_deleted', '0')
        //         ->where('courses.semester_id', $semester_id)->get();
        //     return $projects;
        // } catch (Throwable $e) {

        //     return false;
        // }
        if(Auth::user()->role_id == '3'){
            $projects = ProjectCourse::join('courses', 'courses.id', 'project_courses.course_id')
            ->Join('projects', 'projects.id', '=', 'project_courses.project_id')
            ->where('project_courses.status', '2')->where('project_courses.is_deleted', '0')
            ->where('courses.faculty_id', Auth::user()->university_users->id)
            ->where('courses.semester_id', $semester_id)->count();
        }else{
            $projects = ProjectCourse::join('courses', 'courses.id', 'project_courses.course_id')
            ->Join('projects', 'projects.id', '=', 'project_courses.project_id')
            ->where('project_courses.status', '2')->where('project_courses.is_deleted', '0')
            ->where('courses.semester_id', $semester_id)->count();
        }
        return $projects;
    }

    /**
     * Get the all teams
     * @return void
     */
    public function getAllTeams($semester_id)
    {
        //dd($semester_id);
        try {
            if(Auth::user()->role_id == '3'){
                $teams = Team::select('*')
                ->join('project_courses', 'project_courses.id', 'teams.project_course_id')
                ->join('courses', 'courses.id', 'project_courses.course_id')
                ->where('courses.semester_id', $semester_id)
                ->where('courses.faculty_id', Auth::user()->university_users->id)
                ->where('teams.is_deleted', 0)->get();
            }else{
                $teams = Team::select('*')
                ->join('project_courses', 'project_courses.id', 'teams.project_course_id')
                ->join('courses', 'courses.id', 'project_courses.course_id')
                ->where('courses.semester_id', $semester_id)
                ->where('teams.is_deleted', 0)->get();
            }
            //dd($teams);
            return $teams;
        } catch (Throwable $e) {

            return false;
        }
    }

    /**
     * Get the all clients
     * @return void
     */
    public function getAllClients($semester_id)
    {
        try {
            //dd($semester_id);
            $client = Project::select('*')
                ->join('project_courses', 'project_courses.project_id', 'projects.id')
                ->join('courses', 'courses.id', 'project_courses.course_id')
                ->where('courses.semester_id', $semester_id)->groupBy('projects.client_id')
                //bellow commented line will be uncomment if want status is 1
                //->where('status', '1')
                ->get();
            return $client;
        } catch (Throwable $e) {

            return false;
        }
    }

    /**
     * Get the all completed projects
     * @return void
     */
    public function getAllCompletedProjects()
    {
        try {
            $client = Project::with(['project_categories', 'usercreated_by', 'project_course', 'usercreated_by'=>function($q){
                $q->with(['university_users'=>function($q){
                    $q->with('user_profiles')->get();
                },])->get();
            }])->select('*')->where('status', '5')->get();
            return $client;
        } catch (Throwable $e) {

            return false;
        }
    }

    /**
     * Get the all faculties
     * @return void
     */
    public function getAllFaculties($semester_id)
    {
        if(Auth::user()->role_id == '3'){
            $faculties = Course::where('semester_id', $semester_id)->where('faculty_id', Auth::user()->university_users->id)->count();
        }else{
            $faculties = Course::where('semester_id', $semester_id)->where('faculty_id', '!=', '')->count();
        }
        return $faculties;
    }

    /**
     * Get the all faculties
     * @return void
     */
    public function getAllFacultiesForEmail()
    {
        $faculties = User::with('university_users')->select('id', 'first_name', 'last_name', 'email')->where('is_deleted', 0)->where('role_id', 3)->get();
        return $faculties;
    }

    /**
     * Get the all students
     * @return void
     */
    public function getAllStudents($semester_id)
    {
        if(Auth::user()->role_id == '3'){
            $students = CourseStudent::join('courses', 'courses.id', 'course_students.course_id')
            ->join('university_users', 'university_users.id', 'course_students.student_id')
            ->join('users', 'users.id', 'university_users.user_id')
            ->where('courses.semester_id', $semester_id)
            ->where('courses.faculty_id', Auth::user()->university_users->id)
            ->where('users.is_deleted', '0')->groupBy('course_students.student_id')->get();
        }else{
            $students = CourseStudent::join('courses', 'courses.id', 'course_students.course_id')
            ->join('university_users', 'university_users.id', 'course_students.student_id')
            ->join('users', 'users.id', 'university_users.user_id')
            ->where('courses.semester_id', $semester_id)->where('users.is_deleted', '0')->groupBy('course_students.student_id')->get();
        }
        return $students;
    }

    /**
     * Get the all files
     * @return void
     */
    public function getAllFiles($semester_id)
    {
        if(Auth::user()->role_id == '3'){
            $files = File::join('teams', 'teams.id', 'files.entity_id')
            ->join('project_courses', 'project_courses.id', 'teams.project_course_id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->where('semester_id', $semester_id)
            ->where('courses.faculty_id', Auth::user()->university_users->id)
            ->where('entity_type', 'team_files')->count();
        }else{
            $files = File::join('teams', 'teams.id', 'files.entity_id')
            ->join('project_courses', 'project_courses.id', 'teams.project_course_id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->where('semester_id', $semester_id)
            ->where('entity_type', 'team_files')->count();
        }
        return $files;
    }

    /**
     * Get the all milestones
     * @return void
     */
    public function getAllMilestoneCount($semester_id)
    {
        if(Auth::user()->role_id == '3'){
            $milestones = ProjectMilestone::join('teams', 'teams.id', 'project_milestones.team_id')
            ->join('project_courses', 'project_courses.id', 'teams.project_course_id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->where('semester_id', $semester_id)
            ->where('courses.faculty_id', Auth::user()->university_users->id)->count();
        }else{
            $milestones = ProjectMilestone::join('teams', 'teams.id', 'project_milestones.team_id')
            ->join('project_courses', 'project_courses.id', 'teams.project_course_id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->where('semester_id', $semester_id)->count();
        }
        return $milestones;
    }

    /**
     * Get the all completed milestones
     * @return void
     */
    public function getAllCompletedMilestone($semester_id)
    {   
        if(Auth::user()->role_id == '3'){
            $completedMilestones = ProjectMilestone::join('teams', 'teams.id', 'project_milestones.team_id')
            ->join('project_courses', 'project_courses.id', 'teams.project_course_id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->join('milestone_progresses', 'milestone_progresses.milestone_id', 'project_milestones.milestone_id')
            ->where('semester_id', $semester_id)->where('milestone_progresses.status', 'Completed')
            ->where('milestone_progresses.is_deleted', '0')
            ->where('courses.faculty_id', Auth::user()->university_users->id)->count();
        }else{
            $completedMilestones = ProjectMilestone::join('teams', 'teams.id', 'project_milestones.team_id')
            ->join('project_courses', 'project_courses.id', 'teams.project_course_id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->join('milestone_progresses', 'milestone_progresses.milestone_id', 'project_milestones.milestone_id')
            ->where('semester_id', $semester_id)->where('milestone_progresses.status', 'Completed')->where('milestone_progresses.is_deleted', '0')->count();
        }
        return $completedMilestones;
    }

    /**
     * Get the all courses
     * @return void
     */
    public function getAllCourses($semester_id)
    {
        if(Auth::user()->role_id == '3'){
        $courses = Course::where('courses.semester_id', $semester_id)->where('courses.faculty_id', Auth::user()->university_users->id)->count();
        }else{
            $courses = Course::where('courses.semester_id', $semester_id)->count();
        }
        return $courses;
    }

    /**
     * Get the all tasks
     * @return void
     */
    public function getAllTasks($semester_id)
    {
        if(Auth::user()->role_id == '3'){
            $tasks = Task::join('project_courses', 'project_courses.id', 'tasks.project_course_id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->where('courses.semester_id', $semester_id)->where('tasks.is_deleted', 0)
            ->where('courses.faculty_id', Auth::user()->university_users->id)->count();
        }else{
            $tasks = Task::join('project_courses', 'project_courses.id', 'tasks.project_course_id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->where('courses.semester_id', $semester_id)->where('tasks.is_deleted', 0)->count();
        }
        return $tasks;
    }

    /**
     * Get the all completed tasks
     * @return void
     */
    public function getAllCompletedTasks($semester_id)
    {
        if(Auth::user()->role_id == '3'){
            $completedTasks = Task::join('project_courses', 'project_courses.id', 'tasks.project_course_id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->where('courses.semester_id', $semester_id)->where('tasks.is_deleted', 0)
            ->where('courses.faculty_id', Auth::user()->university_users->id)->where('tasks.status', '1')->count();
        }else{
            $completedTasks = Task::join('project_courses', 'project_courses.id', 'tasks.project_course_id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->where('courses.semester_id', $semester_id)->where('tasks.is_deleted', 0)->where('tasks.status', '1')->count();
        }
        return $completedTasks;
    }
    
    /**
     * Get the all assigned projects to faculty
     * @param int $user_id
     * @return void
     */
    public function assignedProjects($user_id)
    {
        try {     
            $user=Auth::user();
            // DB::enableQueryLog(); 
            if($user->role_id == 3){
                $projects = Course::join('university_users', 'university_users.id', '=', 'courses.faculty_id')
                ->join('users', 'users.id', '=', 'university_users.user_id')
                ->join('project_courses', 'project_courses.course_id', '=', 'courses.id')
                ->join('projects', 'projects.id', '=', 'project_courses.project_id')
                ->select('projects.id as project_id', 'project_courses.id as projectCourseId', 'projects.client_id', 'projects.status')
                ->where('courses.faculty_id', $user->university_users->id)
                ->orderBy('project_courses.id', 'DESC')->get();
            }elseif($user->role_id == 5){
                $projects = Course::join('university_users', 'university_users.id', '=', 'courses.faculty_id')
                ->join('users', 'users.id', '=', 'university_users.user_id')
                ->join('project_courses', 'project_courses.course_id', '=', 'courses.id')
                ->join('projects', 'projects.id', '=', 'project_courses.project_id')
                ->select('projects.id as project_id', 'project_courses.id as projectCourseId', 'projects.client_id', 'projects.status')
                ->where('courses.ta_id', $user->university_users->id)
                ->orderBy('project_courses.id', 'DESC')->get();
            }else{
            $projects = Course::join('university_users', 'university_users.id', '=', 'courses.faculty_id')
                ->join('users', 'users.id', '=', 'university_users.user_id')
                ->join('project_courses', 'project_courses.course_id', '=', 'courses.id')
                ->join('projects', 'projects.id', '=', 'project_courses.project_id')
                ->select('project_courses.id as projectCourseId', 'projects.client_id', 'projects.status')
                ->where('courses.ta_id', $user->university_users->id)->get();      
                // dd(DB::getQueryLog());
             //   dd($projects);
            }
            return $projects;
        } catch (Throwable $e) {

            return false;
        }
    }

    /**
     * Get the all completed projects to TA
     * @param int $user_id
     * @return void
     */
    public function completedProjects($user_id)
    {
        try {     
            $user=Auth::user();
            // DB::enableQueryLog();             
            $projects = Course::join('university_users', 'university_users.id', '=', 'courses.faculty_id')
            ->join('users', 'users.id', '=', 'university_users.user_id')
            ->join('project_courses', 'project_courses.course_id', '=', 'courses.id')
            ->join('projects', 'projects.id', '=', 'project_courses.project_id')
            ->select('projects.id as project_id', 'project_courses.id as projectCourseId', 'projects.client_id', 'projects.status')
            ->where('courses.ta_id', $user->university_users->id)
            ->where('projects.status', 5)
            ->where('project_courses.status', 5)
            ->orderBy('projects.created_at', 'DESC')->get();
            
            return $projects;
        } catch (Throwable $e) {

            return false;
        }
    }

 /**
     * Get the all unassigned students
     * @param int $student, $course
     * @return void
     */

    public function unassignStudent($student = null, $course = null)
    {
        //dd($student);
        try {
            $university_user =  DB::table('university_users')->select('id')
                ->Where('user_id', $student)->get()->first();            
            
            $result = $this->deleteStudentFromTeams($university_user->id, $course);
            $result = $this->updateStudentCount($course);
            
            DB::table('course_students')
                ->where('student_id', $university_user->id)
                ->where('course_id', $course)->delete();
            
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
 /**
     * delete student
     * @param int $student
     * @return void
     */
    public function deleteStudent($student, $dateTime)
    {
        try {
            $university_user =  DB::table('university_users')->select('id')
                ->Where('user_id', $student)->get()->first();
            $result = DB::table('course_students')->where('student_id', $university_user->id)->get();
            //dd($result);
            if($result){
                foreach($result as $data){
                    $this->updateStudentCount($data->course_id);
                    DB::table('course_students')
                    ->where('student_id', $data->student_id)
                    ->where('course_id', $data->course_id)->delete();
                }
            }
            DB::table('users')->where('id', $student)->update(['is_deleted' => '1', 'updated_at' => $dateTime]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }


     /**
     * course student list
     * @param int $course_id
     * @return void
     */
    public function courseStudentList($course_id)
    {
        try {
            $assignedStudents = $newStudentsArray = [];
            $alreadyAssigned = DB::table('course_students')->select('course_students.*')
                ->where('course_id', $course_id)->get();
            foreach ($alreadyAssigned as $users) {
                $assignedStudents[] = $users->student_id;
            }
         
            $newStudents = DB::table('users')->select('university_users.id as user_id')
                ->join('university_users', 'university_users.user_id', '=', 'users.id')
                ->where('status', '1')
                ->where('role_id', '4')
                ->where('is_deleted', '0')->get();
            
            
            foreach ($newStudents as $key => $value) {
                $newStudentsArray[$key] = $value->user_id;
            }
            $array = array_diff($newStudentsArray, $assignedStudents);
            $result = DB::table('users')->select('users.*', 'university_users.id as user_id')
                ->join('university_users', 'university_users.user_id', '=', 'users.id')
                ->where('status', '1')
                ->where('role_id', '4')
                ->where('is_deleted', '0')
                ->whereIn('university_users.id', $array)->get();
            return $result;
        } catch (Throwable $e) {
            return false;
        }
    }

     /**
     * Assign Course to student
     * @param Array $data
     * @return void
     */
    public function assignCourseToStudent($data)
    {
        DB::table('course_students')->insert($data);
        return;
    }

    /**
     * Get the all assigned projects to faculty
     * @param int $user_id
     * @return void
     */
    public function approvedProjects($user_id)
    {
        try {
            $projects = Course::join('university_users', 'university_users.id', '=', 'courses.faculty_id')
                ->join('users', 'users.id', '=', 'university_users.user_id')
                ->join('project_courses', 'project_courses.course_id', '=', 'courses.id')
                ->join('projects', 'projects.id', '=', 'project_courses.project_id')
                ->select('projects.*')
                ->where('university_users.user_id', $user_id)->get();
            return $projects;
        } catch (Throwable $e) {

            return false;
        }
    }

   /**
     * Get the all requested project
     * @param Array $data
     * @return void
     */

    public function requestProject($data)
    {
        //dd($data);
        //$projectRequest = $data;
        //dd($projectRequest);
        if (ProjectRequest::insert($data)) {
            //for store media into upload table
            $message = 'Project request sent successfully.';
        } else {
            $message = 'Unable to process project request.';
        }
        return $message;
    }

     /**
     * Get the all requested project
     * @return void
     */
    public function allRequestedProjects()
    {
        $projects = ProjectRequest::with(['projects', 'users.university_users'])
            ->join('projects', 'projects.id', '=', 'project_requests.project_id')
            ->where('projects.status', 1)->where('projects.is_assigned', 1)
            ->select('project_requests.*')->orderBy('id','desc')->get();

        $result = ProjectRequest::where('is_seen', 0)->update(['is_seen' => 1]);
        return $projects;
    }

    /**
     * Approve Deny Project Request
     * @param int $id, Array $data
     * @return void
     */
    public function approveDenyProjectRequest($data, $id)
    {
        $result = ProjectRequest::where('id', $id)->update($data);
        if ($result == 1) {
            if($data['status'] == 1){
                $message = array('status' => 'success', 'msg' => 'Project request approved successfully');
            }elseif($data['status'] == 2){
                $message = array('status' => 'success', 'msg' => 'Project request denied successfully');
            }
        } else {
            $message = array('status' => 'failed', 'msg' => 'Unable to process project request.');
        }
        return $message;
    }

     /**
     * revoke project
     * @param int $id, Array $data
     * @return void
     */
    public function revokeProject($data, $id)
    {
        $result = ProjectRequest::where('id', $id)->update($data);
        if ($result == 1) {
            $message = array('status' => 'success', 'msg' => 'Project request revoked successfully.');
        } else {
            $message = array('status' => 'failed', 'msg' => 'Unable to revoke project request.');
        }
        return $message;
    }

     /**
     * Project Assign for faculty for Pool
     * @param Array $data
     * @return void
     */
    public function projectAssignForFacultyPool($data, $updatedAt)
    {
        try {
            Project::whereIn('id', $data)->update(['is_assigned' => 1, 'updated_at' => $updatedAt]);
            return;
        } catch (Throwable $e) {
            return false;
        }
    }


     /**
     * Add Task
     * @param object $request
     * @return void
     */

    public function addTask($request)
    {
        try {
            $teamArray = $this->getTeamForTask($request->project_course_id);
            $maxOrderCounter = Task::max('order_counter');
            $data = array(
                'title' => $request->title,
                'description' => $request->description,
                'change_request_id' => $request->change_request_id,
                'project_course_id' => $request->project_course_id,
                'order_counter' => $maxOrderCounter + 1,
                'created_by' => $request->created_by,
                'updated_by' => $request->created_by,
                'created_at' => $request->created_at,
                'updated_at' => $request->updated_at
            );
            $result =   Task::insert($data);
            $taskId = DB::getPdo()->lastInsertId();
            $dataArray = [];
            if(isset($request->team_id)){
                $dataArray['task_id'] = $taskId;
                $dataArray['team_id'] = $request->team_id;
                $dataArray['created_by'] = $request->created_by;
                $dataArray['updated_by'] = $request->created_by;
                $dataArray['created_at'] = $request->created_at;
                $dataArray['updated_at'] = $request->updated_at;
            }else{
                foreach ($teamArray as $key => $team) {
                    $dataArray[$key]['task_id'] = $taskId;
                    $dataArray[$key]['team_id'] = $team->team_id;
                    $dataArray[$key]['created_by'] = $request->created_by;
                    $dataArray[$key]['updated_by'] = $request->created_by;
                    $dataArray[$key]['created_at'] = $request->created_at;
                    $dataArray[$key]['updated_at'] = $request->updated_at;
                }
            }
            $result =   TeamTask::insert($dataArray);
            return $taskId;
        } catch (Throwable $e) {
            return false;
        }
    }



     /**
     * get team for task
     * @param int $projectCourseId
     * @return void
     */
    public function getTeamForTask($projectCourseId = null)
    {
        try {
            $teams = Team::join('project_courses', 'project_courses.id', '=', 'teams.project_course_id')
                ->select('teams.id as team_id')
                ->where('project_courses.id', $projectCourseId)->get();
            return $teams;
        } catch (Throwable $e) {

            return false;
        }
    }

     /**
     * Get Task List
     * @param int $roleId, $project_course_id, $teamId, String $createdBy
     * @return void
     */

    public function getTasks($createdBy = null, $roleId = null, $project_course_id = null, $teamId = null)
    {
        //  DB::enableQueryLog();
        
            if ($roleId == '2') {
                $query = Task::with(['change_request', 'project_courses', 'team_task', 'task_files'])
                    ->join('change_requests', 'change_requests.id', '=', 'tasks.change_request_id')
                    ->join('university_users as uu1', 'uu1.id', '=', 'tasks.created_by')
                    ->join('users as created_by_user', 'created_by_user.id', '=', 'uu1.user_id')
                    ->join('university_users as uu2', 'uu2.id', '=', 'tasks.updated_by')
                    ->join('users as updated_by_user', 'updated_by_user.id', '=', 'uu2.user_id')
                    ->select('tasks.*', 'created_by_user.first_name as created_first_name', 'created_by_user.last_name as created_last_name', 'created_by_user.profile_image as created_profile_image', 'updated_by_user.first_name as updated_first_name', 'updated_by_user.last_name as updated_last_name', 'updated_by_user.profile_image as updated_profile_image')
                    ->where('change_requests.is_deleted', '0')
                    ->where('tasks.is_deleted', '0')
                    ->where('change_requests.created_by', $createdBy);
                if ($project_course_id != '') {
                    $query->where('tasks.project_course_id', $project_course_id);
                }
                $tasks = $query->orderBy('order_counter', 'asc')->get();
            } else {
                if ($roleId == '1') {
                    $query = Task::with(['change_request', 'project_courses', 'team_task', 'task_files'])
                        ->join('team_tasks', 'team_tasks.task_id', '=', 'tasks.id')
                        ->join('teams', 'teams.id', '=', 'team_tasks.team_id')
                        //->join('change_requests', 'change_requests.id', '=', 'tasks.change_request_id')
                        ->join('university_users as uu1', 'uu1.id', '=', 'tasks.created_by')
                        ->join('users as created_by_user', 'created_by_user.id', '=', 'uu1.user_id')
                        ->join('university_users as uu2', 'uu2.id', '=', 'tasks.updated_by')
                        ->join('users as updated_by_user', 'updated_by_user.id', '=', 'uu2.user_id')
                        ->join('university_users as taskCompletedBy', 'taskCompletedBy.id', '=', 'team_tasks.updated_by')
                        ->join('users as completed_by', 'completed_by.id', '=', 'taskCompletedBy.user_id')
                        ->select('tasks.*', 'created_by_user.first_name as created_first_name', 'created_by_user.last_name as created_last_name', 'created_by_user.profile_image as created_profile_image', 'updated_by_user.first_name as updated_first_name', 'updated_by_user.last_name as updated_last_name', 'updated_by_user.profile_image as updated_profile_image', 'team_tasks.is_completed', 'team_tasks.updated_by', 'completed_by.first_name as completed_by_first_name', 'completed_by.last_name as completed_by_last_name', 'team_tasks.updated_at as completed_at', 'teams.name as teamName');
                    //->where('change_requests.is_deleted', '0');
                    if ($project_course_id != '') {
                        $query->where('tasks.project_course_id', $project_course_id);
                    }
                    if ($teamId != '') {
                        $query->where('team_tasks.team_id', $teamId);
                    }
                    $query->where('tasks.is_deleted', '0');
                    $tasks = $query->orderBy('order_counter', 'asc')->get();
                    // dd($tasks);
                } else {
                    $query = Task::with(['change_request', 'project_courses', 'team_task', 'task_files'])
                        ->join('team_tasks', 'team_tasks.task_id', '=', 'tasks.id')
                        ->join('teams', 'teams.id', '=', 'team_tasks.team_id')
                        //->join('change_requests', 'change_requests.id', '=', 'tasks.change_request_id')
                        ->join('university_users as uu1', 'uu1.id', '=', 'tasks.created_by')
                        ->join('users as created_by_user', 'created_by_user.id', '=', 'uu1.user_id')
                        ->join('university_users as uu2', 'uu2.id', '=', 'tasks.updated_by')
                        ->join('users as updated_by_user', 'updated_by_user.id', '=', 'uu2.user_id')
                        ->join('university_users as taskCompletedBy', 'taskCompletedBy.id', '=', 'team_tasks.updated_by')
                        ->join('users as completed_by', 'completed_by.id', '=', 'taskCompletedBy.user_id')
                        ->select('tasks.*', 'created_by_user.first_name as created_first_name', 'created_by_user.last_name as created_last_name', 'created_by_user.profile_image as created_profile_image', 'updated_by_user.first_name as updated_first_name', 'updated_by_user.last_name as updated_last_name', 'updated_by_user.profile_image as updated_profile_image', 'team_tasks.is_completed', 'team_tasks.updated_by', 'completed_by.first_name as completed_by_first_name', 'completed_by.last_name as completed_by_last_name', 'team_tasks.updated_at as completed_at', 'teams.name as teamName');
                        //->where('change_requests.is_deleted', '0')
                        if ($roleId == '3' || $roleId == '5') {
                        $query->where('tasks.created_by', $createdBy);
                        }
                    if ($project_course_id != '') {
                        $query->where('tasks.project_course_id', $project_course_id);
                    }
                    if ($teamId != '') {
                        $query->where('team_tasks.team_id', $teamId);
                    }
                    $query->where('tasks.is_deleted', '0');
                    $tasks = $query->orderBy('order_counter', 'asc')->get();
                }
            }
            // dd(DB::getQueryLog()); 
            // dd($tasks);
            return $tasks;
          
    }


     /**
     * Update Task Order
     * @param Array $request
     * @return void
     */
    public function updateTaskOrder($request)
    {
        try {
            $order_counter = 1;
            foreach ($request->id as $key => $data) {
                //$dataArray[$key]['id'] = $data;
                $dataArray['order_counter'] = $order_counter;
                $order_counter++;
                DB::table('tasks')
                    ->where('id', $data)
                    ->update($dataArray);
            }
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get project client
     * @param $project_id
     * @return object
     */

    public function getClientName($project_id)
    {
        try {
           $created_by = Project::with(['usercreated_by.university_users'])->where('id', $project_id)->first();
           return $created_by;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get All Pm plans
     *
     * @return object 
     */

    public function getAllPmPlans()
    {

        $result = $array = [];
        if(Auth::user()->role_id == 3){
            $result = Course::select('ta_id as userId')->where('faculty_id', Auth::user()->university_users->id)
            ->where('ta_id', '!=', null)->get();
        }
        if(Auth::user()->role_id == 5){
            $result = Course::select('faculty_id as userId')->where('ta_id', Auth::user()->university_users->id)
            ->where('faculty_id', '!=', null)->get();
        }
        foreach($result as $key => $data){
            $array[] = $data->userId;
        }
        $uniqueArray = array_unique($array);
        $array = implode(',', $uniqueArray);
        //dd($array);
        $query =   DB::table('pm_plans')->select('pm_plans.id', 'pm_plans.name', 'users.role_id')->where('pm_plans.is_deleted', '0')
        ->join('university_users', 'university_users.id', '=', 'pm_plans.created_by')
        ->join('users', 'users.id', '=', 'university_users.user_id');
        if(Auth::user()->role_id != 1){
            $query->where('pm_plans.created_by', Auth::user()->university_users->id)
                ->orWhere('pm_plans.created_by', 5)
                ->orWhereIn('pm_plans.created_by', [$array]);
        }
        return $query->orderBy('users.role_id', 'asc')->get()->all();
    }

    /**
     * Get Milestones for a plan
     * 
     * @param int $plan_id
     * @return object
     */

    public function getMilestones($plan_id)
    {

        $milestones =   DB::table('milestones')->where('plan_id', $plan_id)->where('is_deleted', 0)->get()->all();
        return $milestones;
    }

    /**
     * Get Milestones for a course
     * 
     * @param int $course_id
     * @return object
     */

    public function getTeamsByProjectCourseId($project_course_id)
    {
        //  DB::enableQueryLog();
        $teams =   Team::where('project_course_id', $project_course_id)->where('is_deleted', 0)->get()->all();
        //  dd(DB::getQueryLog());
        return $teams;
    }

    /**
     * Assign Milestones
     * 
     * @param Array $data
     * @return Boolean
     */

    public function assignMilestone($data)
    {

        DB::table('project_milestones')->insert($data);
        return true;
    }

    /**
     * Update Milestones Deadline
     * 
     * @param Array $data
     * @return Boolean
     */

     public function updateDeadline($mileStoneId, $deadline)
     {
         $data['deadline'] = Carbon::createFromFormat('m-d-Y', $deadline)->format('Y-m-d');
         DB::table('milestones')->where('id', $mileStoneId)->update($data);
         return true;
     }

    /**
     * Set plan as active
     * 
     * @param $course_id
     * @return Boolean
     */

    public function setPmPlanActive($plan_id, $updatedAt)
    {

        $update['updated_at'] = $updatedAt;
        $update['is_active'] = '1';
        DB::table('pm_plans')->where('id', $plan_id)->update($update);
        return true;

       }

        /**
         * Peer Evalutation List
         * @return Boolean
         */
       public function peerEvaluationList()
       {
           try{
            $peearEvaluation = DB::table('peer_evaluations')->where('is_active', '1')->where('is_deleted', '0')->get()->all();
            return $peearEvaluation;
           } catch (Throwable $e){
            return false;
           }
       }

       /**
         * Peer Evalutation List for faculty and TA
         * @return Boolean
         */
        public function peerEvaluationListForFacultyAndTA()
        {
            try{
             $peearEvaluation = DB::table('peer_evaluations')->where('is_active', '1')->where('is_deleted', '0')->get()->all();
             return $peearEvaluation;
            } catch (Throwable $e){
             return false;
            }
        }


        /**
         * Update studens count in course_students table      
         * @param int $course
         * @return Boolean
         */
       public function updateStudentCount($course = null)
       {
            $result = Course::where('id', $course)->get()->first();            
            $count = $result->student_count-1;
            DB::table('courses')
            ->where('id', $course)
            ->update(['student_count' => $count]);
            //dd($count);

       }

       /**
         * Update team studens when unassigning view_users page      
         * @param int $studentId | $course
         */
        public function deleteStudentFromTeams($studentId = null, $course = null)
        {
            $proCourse = ProjectCourse::select('team_students.id')
                ->join('teams', 'teams.project_course_id', '=', 'project_courses.id')
                ->join('team_students', 'team_students.team_id', '=', 'teams.id')
                ->where('team_students.student_id',$studentId)
                ->where('project_courses.course_id',$course)
                ->where('project_courses.is_deleted', 0)
                ->where('team_students.is_deleted', 0)->get()->toArray();
            
            if($proCourse){
                TeamStudent::whereIn('id', $proCourse)->update(['is_deleted' => 1]); 
            }
        }

      
       /**
         * Add studens count in course_students table     
         * @param int $course, int $count
         * @return Boolean
         */
       public function addStudentCount($course = null, $count = null)
       {
        $result = Course::where('id', $course)->get()->first();            
        $count = $result->student_count+$count;
        DB::table('courses')
        ->where('id', $course)
        ->update(['student_count' => $count]);
       }

       /**
         * get heading or title    
         * @param int $course
         * @return Object
         */
       public function getHeading($course = null)
       {
        $result = Course::join('semesters', 'semesters.id', '=', 'courses.semester_id')
        ->select('prefix', 'number', 'section', 'semester', 'year')
        ->where('courses.id', $course)->get()->first();
        return $result;
       }

       /**
     * get semister
     * @param Int $course_id
     * @param 
     * @return HTML
     */
    public function getSemester($course_id)
    {
        $query = DB::table('courses')->select('semesters.id')
            ->leftJoin('semesters', 'semesters.id', '=', 'courses.semester_id')
            ->where('courses.id', $course_id)->get()->first();

        return $query;
    }

    /**
     * Get the project teams using project ID, 
     * Join courses, teams and where teams created by auther
     * @param Int $semId
     * @return void
     */
    public function getSemCourses($semId)
    {
        $teams = Course::select('id', 'prefix','number','section')
                ->where('semester_id', $semId)->get();
            return $teams;
    }

        /**
         * Get Project By Project course Id    
         * @param Int $ids
         * @return Object
         */
    public function getProjectByProjectCourseId($ids)
    { 
        $projects=[];
        // DB::enableQueryLog();
            $projectResult= 
            ProjectCourse::select('project_courses.*')->with('projects','courses.semesters')
                            ->join('projects', 'projects.id','project_courses.project_id')
                            ->where('projects.status',2)
                            ->whereIn('project_courses.id', $ids)->get()->all(); 
                                    //  dd(DB::getQueryLog());
          
        foreach($projectResult as $key => $projectArray)
        {       
                $enrollmentStatus = $this->enrollmentStatus($projectArray->id);
                if($enrollmentStatus){
                    $projects[$key]['project_course_id'] = $projectArray->id;
                    $projects[$key]['course_id'] = $projectArray->courses->id;
                    $projects[$key]['title'] = $projectArray->courses->semesters->semester.' '.$projectArray->courses->semesters->year.'-'.$projectArray->courses->prefix.' '.$projectArray->courses->number.' '.$projectArray->courses->section.'-'.$projectArray->projects->title;
                    $projects[$key]['status'] = $projectArray->projects->status;
                }
        }
        $projects = json_decode(json_encode($projects));
        
        return $projects;
    }

    /**
     * Check if all team enrollment is full    
     * @param Int $projectCourseId
     * @return boolean
     */
    public function enrollmentStatus($projectCourseId)
    {
        $result = TeamStudentCount::where('team_student_counts.project_course_id', $projectCourseId)
        ->join('teams', 'teams.id', '=', 'team_student_counts.team_id')
        ->where('teams.is_deleted', Config::get('constants.is_deleted.false'))->get();
        
        foreach($result as $key => $data){
            $count = TeamStudent::where('team_id', $data->team_id)->where('is_deleted', 0)->count();
            if($count < $data->students_per_team){
                $statusArray[$projectCourseId][] = true;
            }else{
                $statusArray[$projectCourseId][] = false;
            }
        }
        if(in_array(true, $statusArray[$projectCourseId])){
            return true;
        }else{
            return false;
        }
    }

     /**
         * Get Project Course By Project Course Id
         * @param Int $projectcourseId
         * @return Boolean
         */
    public function getProjectCoursebyProjectCourseId($projectCourseId){
        return ProjectCourse::select('project_id' , 'course_id')->where('id',$projectCourseId)->first();
    }

    /**
     * Communication setting within project
     * 
     * @param Array $insertArray
     * @return Boolean
     */

    public function projectCourseSetting($insertArray)
    {
        if (DB::table('project_course_settings')->insert($insertArray)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Edit communication setting within project
     * @param Array $updateArray
     * @return Boolean
     */

    public function editProjectCourseSetting($updateArray)
    {
        foreach ($updateArray as $key => $value) {
            DB::table('project_course_settings')->where('id', $value['id'])->update($value);
        }
        return true;
    }

     /**
         * Get chat Client list 
         * @param Int $projectcourseId 
         * @return Array
         */
    public function getChatClient($projectCourseId = null)
    {
        $dataArray = [];
        $client = ProjectCourse::with('projects.client.university_users')
        ->where('id', $projectCourseId)->get();
        foreach($client as $key => $data){
            $dataArray[$key]['id'] = $data->projects->client_id;
            $dataArray[$key]['first_name'] = $data->projects->client->university_users->first_name;
            $dataArray[$key]['last_name'] = $data->projects->client->university_users->last_name;
            $dataArray[$key]['profile_image'] = $data->projects->client->university_users->profile_image;
            $dataArray[$key]['role_name'] = 'Client';
            $dataArray[$key]['project_id'] = $data->project_id;
            $dataArray[$key]['team_id'] = $data->projects->client->university_users->profile_image;
            $msgCount = $this->messageObj->message_count($data->projects->client_id, Auth::user()->university_users->id);
            if ($msgCount > 0) {
                $unreadMsgCount = $msgCount;
                $newClass = 'msg-bg-color';
            } else {
                $unreadMsgCount = $newClass = '';
            }
            $dataArray[$key]['msg_count'] = $unreadMsgCount;
            $dataArray[$key]['unreadMsgClass'] = $newClass;
        }
        return $dataArray;
    }

    /**
         * Get chat Client list 
         * @param Int $projectcourseId 
         * @return Array
         */
        public function getChatAdmin($projectCourseId = null)
        {
            $dataArray = [];
            $admin = User::select('university_users.id')->join('university_users', 'university_users.user_id', 'users.id')
            ->where('users.role_id', 1)->first();
            
            $data = Message::join('university_users', 'university_users.id', '=', 'messages.sender_id')
            ->join('users', 'users.id', '=', 'university_users.user_id')->where('receiver_id', Auth::user()->university_users->id)->
            where('sender_id', $admin->id)->orderBy('messages.id', 'desc')->first();
            if($data){
                $dataArray['id'] = $admin->id;
                $dataArray['first_name'] = $data->first_name;
                $dataArray['last_name'] = $data->last_name;
                $dataArray['profile_image'] = $data->profile_image;
                $dataArray['role_name'] = 'Admin';
                $dataArray['project_id'] = $projectCourseId;
                $dataArray['team_id'] = $data->profile_image;
                $msgCount = $this->messageObj->message_count(5, Auth::user()->university_users->id);
                if ($msgCount > 0) {
                    $unreadMsgCount = $msgCount;
                    $newClass = 'msg-bg-color';
                } else {
                    $unreadMsgCount = $newClass = '';
                }
                $dataArray['msg_count'] = $unreadMsgCount;
                $dataArray['unreadMsgClass'] = $newClass;
            }
            return $dataArray;
        }
    /**
         * Get chat Client list 
         * @param Int $projectcourseId 
         * @return Array
         */
        public function getAdminChat($projectCourseId = null)
        {
            $dataArray = [];
            $adminList = User::select('university_users.id')->join('university_users', 'university_users.user_id', 'users.id')
            ->where('users.role_id', 1)->get();
            foreach($adminList as $key => $admin){
                $data = Message::join('university_users', 'university_users.id', '=', 'messages.sender_id')
                ->join('users', 'users.id', '=', 'university_users.user_id')->where('receiver_id', Auth::user()->university_users->id)->
                where('sender_id', $admin->id)->orderBy('messages.id', 'desc')->first();
                if($data){
                    $dataArray[$admin->id]['id'] = $admin->id;
                    $dataArray[$admin->id]['first_name'] = $data->first_name;
                    $dataArray[$admin->id]['last_name'] = $data->last_name;
                    $dataArray[$admin->id]['profile_image'] = $data->profile_image;
                    $dataArray[$admin->id]['role_name'] = 'Admin';
                    $dataArray[$admin->id]['project_id'] = $projectCourseId;
                    $dataArray[$admin->id]['team_id'] = $data->profile_image;
                    $msgCount = $this->messageObj->message_count($admin->id, Auth::user()->university_users->id);
                    if ($msgCount > 0) {
                        $unreadMsgCount = $msgCount;
                        $newClass = 'msg-bg-color';
                    } else {
                        $unreadMsgCount = $newClass = '';
                    }
                    $dataArray[$admin->id]['msg_count'] = $unreadMsgCount;
                    $dataArray[$admin->id]['unreadMsgClass'] = $newClass;
                }
            }
            return $dataArray;
        }

     /**
         * Get chat Instructor list 
         * @param Int $projectcourseId 
         * @return Array
         */
    public function getChatInstructor($projectCourseId = null)
    {
        $dataArray = [];
        $instructor = ProjectCourse::with('courses.faculty_data.university_users','courses.ta_data.university_users')
        ->where('id', $projectCourseId)->get();
        //dd($instructor);
        foreach($instructor as $key => $data){
            if(Auth::user()->role_id != 3){
                if($data->courses->faculty_id){
                    $dataArray[0]['id'] = $data->courses->faculty_id;
                    $dataArray[0]['first_name'] = $data->courses->faculty_data->university_users->first_name;
                    $dataArray[0]['last_name'] = $data->courses->faculty_data->university_users->last_name;
                    $dataArray[0]['profile_image'] = $data->courses->faculty_data->university_users->profile_image;
                    $dataArray[0]['role_name'] = 'Faculty';
                    $dataArray[0]['project_id'] = $data->project_id;
                    $dataArray[0]['team_id'] = null;
                    $msgCount = $this->messageObj->message_count($data->courses->faculty_id, Auth::user()->university_users->id);
                    if ($msgCount > 0) {
                        $unreadMsgCount = $msgCount;
                        $newClass = 'msg-bg-color';
                    } else {
                        $unreadMsgCount = $newClass = '';
                    }
                    $dataArray[0]['msg_count'] = $unreadMsgCount;
                    $dataArray[0]['unreadMsgClass'] = $newClass;
                }
            }
            if(Auth::user()->role_id != 5){
                if($data->courses->ta_id){
                    $dataArray[1]['id'] = $data->courses->ta_id;
                    $dataArray[1]['first_name'] = $data->courses->ta_data->university_users->first_name;
                    $dataArray[1]['last_name'] = $data->courses->ta_data->university_users->last_name;
                    $dataArray[1]['profile_image'] = $data->courses->ta_data->university_users->profile_image;
                    $dataArray[1]['role_name'] = 'TA';
                    $dataArray[1]['project_id'] = $data->project_id;
                    $dataArray[1]['team_id'] = null;
                    $msgCount = $this->messageObj->message_count($data->courses->ta_id, Auth::user()->university_users->id);
                    if ($msgCount > 0) {
                        $unreadMsgCount = $msgCount;
                        $newClass = 'msg-bg-color';
                    } else {
                        $unreadMsgCount = $newClass = '';
                    }
                    $dataArray[1]['msg_count'] = $unreadMsgCount;
                    $dataArray[1]['unreadMsgClass'] = $newClass;
                }
            }
        }
        return $dataArray;
    }

   

     /**
         * Get projects list for TA in milestone and discussions page
         * @return Array
         */
    public function getProjectsForClientMilestoneAndDiscussion()
    {
        $projects = [];
        $projectList = ProjectCourseSetting::with('project_course.courses.semesters', 'project_course.projects')
            ->join('project_courses', 'project_courses.id', '=', 'project_course_settings.project_course_id')
            ->join('projects', 'projects.id', '=', 'project_courses.project_id') 
            ->select('project_courses.id as project_course_id', 'project_courses.course_id as course_id', 'projects.id as project_id', 'project_courses.is_deleted')   
            ->where('project_course_settings.communication_type', 4)
            ->where('project_course_settings.status', 1)
            ->where('projects.status', 2)
            ->where('project_courses.is_deleted', 0)
            ->where('projects.client_id', Auth::user()->university_users->id)->get();
        foreach($projectList as $key => $projectCourse){
            if(!empty($projectCourse->project_course_id)){
                $projects[$projectCourse->project_course_id]['id'] = $projectCourse->project_course_id;
                $projects[$projectCourse->project_course_id]['title'] = $projectCourse->project_course->courses->semesters->semester.' '.$projectCourse->project_course->courses->semesters->year.'-'.$projectCourse->project_course->courses->prefix.' '.$projectCourse->project_course->courses->number.' '.$projectCourse->project_course->courses->section.'-'.$projectCourse->project_course->projects->title;
                $projects[$projectCourse->project_course_id]['status'] = $projectCourse->project_course->projects->status;
            }
        }
        return json_decode(json_encode($projects));
    }

    /**
         * Get projects list for client in milestone page
         * @return Array
         */
        public function getProjectsForClient()
        {
            $projects = [];
            
            $projectList = Project::with(['project_course'=>function($q){
                $q->where('project_courses.is_deleted', '=', 0);
                $q->with(['courses'=>function($q){
                    $q->with('semesters')->get();
                },])->get();
            }])->select('id', 'title', 'status')
                ->where('status', '2')
                ->where('client_id', Auth::user()->university_users->id)->get();

            foreach($projectList as $projectArray){
                foreach($projectArray->project_course as $key => $projectCourse){
                    $projects[$projectCourse->id]['id'] = $projectCourse->id;
                    $projects[$projectCourse->id]['title'] = $projectCourse->courses->semesters->semester.' '.$projectCourse->courses->semesters->year.'-'.$projectCourse->courses->prefix.' '.$projectCourse->courses->number.' '.$projectCourse->courses->section.'-'.$projectArray->title;
                    $projects[$projectCourse->id]['sort'] = $projectCourse->courses->semesters->sort_code;
                }
            }
            return json_decode(json_encode($projects));
        }



      /**
         * Get communication setting
         * @return Object
         */

    public function getCommunicationSettings()
    {
        $result = DB::table('master_settings')
            ->where('type', 'communicationSetting')->get();
        return $result;
    }

    /**
     * Get project id
     * @param  Int $projectCourseId
     * @return Object
     */
    public function getProjectId($projectCourseId)
    {
        $project_course =  DB::table('project_courses')->select('project_id')
            ->where('id', $projectCourseId)->first();
        return $project_course->project_id;
    }


  /**
     * check project course exits
     * @param  Int $projectCourseId
     * @return Object
     */
    public function isProjectCourseExist($projectCourseId = null)
    {
        $teams = Team::where('project_course_id', $projectCourseId)->count();
        if($teams > 0){
            return false;
        }

        $changeRequests = ChangeRequest::where('project_course_id', $projectCourseId)->count();
        if($changeRequests > 0){
            return false;
        }

        $tasks = Task::where('project_course_id', $projectCourseId)->count();
        if($tasks > 0){
            return false;
        }

        $messages = Message::where('project_course_id', $projectCourseId)->count();
        if($messages > 0){
            return false;
        }

        $peerEvaluationStart = PeerEvaluationStart::where('project_course_id', $projectCourseId)->count();
        if($peerEvaluationStart > 0){
            return false;
        }

        $peerEvaluationRatingStar = PeerEvaluationRatingStar::where('project_course_id', $projectCourseId)->count();
        if($peerEvaluationRatingStar > 0){
            return false;
        }

        $evaluationStart = EvaluationStart::where('project_course_id', $projectCourseId)->count();
        if($evaluationStart > 0){
            return false;
        }

        $evaluationQuestionStar = EvaluationQuestionStar::where('project_course_id', $projectCourseId)->count();
        if($evaluationQuestionStar > 0){
            return false;
        }

        $projectCourseSetting = ProjectCourseSetting::where('project_course_id', $projectCourseId)->count();
        if($projectCourseSetting > 0){
            return false;
        }

        return true;
    }

    /**
     * Soft delete project course
     * @param Int $id, Array $data
     * @return  Object $status
     *
     */
    public function deleteCourse($id, $data)
    {
        $status =  ProjectCourse::where('id', $id)->update($data);
        return $status;
    }

    /**
     * Get Course list
     * @param Int $projectCourseId
     * @return  Object $result
     *
     */
    public function getCourseList($projectCourseId = null)
    {
        $result = ProjectCourse::with(['courses.semesters.courses'])
        ->where('id', $projectCourseId)->first();
        return $result;
    }

    /**
     * Is Course Assign
     * @param Int $courseId, $projectId
     * @return  Object $result
     *
     */
    public function isCourseAssign($courseId = null, $projectId = null)
    {
        $result = ProjectCourse::where('project_id', $projectId)->where('course_id', $courseId)->count();
        return $result;
    }


    /**
     * Edit project Course
     * @param Int $id, Array $projectId
     * @return  Object $result
     *
     */
    public function editProjectCourse($id, $data)
    {
        $result = ProjectCourse::where('id', $id)->update(['is_deleted' => 1]);
        if($result){
            $result = ProjectCourse::insert($data);
            return $result;
        }else{
            return $result;
        }
    }
    

    /**
     * Get MilestoneProgress
     * @param Int $milestone_id
     * @return  Object
     */
    public function getMilestoneProgress($milestone_id, $project_milestone_id)
    {
       return MilestoneProgress::where('milestone_id',$milestone_id)->where('project_milestone_id',$project_milestone_id)->where('is_deleted', Config::get('constants.is_deleted.false'))->get();
    }

    /**
	 * Validate project Name (Unique for client)
     * @param Sting $projectName, $client
     * @return  Object
     */
	public function isProjectExist($ProjectName = null, $client = null) {

        if(!$client){
            $client = Auth::user()->university_users->id;
        }
        $checkProjectName = Project::where('title', $ProjectName)->
        where('client_id', $client)->count();
        return $checkProjectName;
	}

    /**
     * Check if PM Plan already exist with same name and created by user
     * @raram Object $request
     * @return Json
     */
    public function unique_pm_plan_name($request)
    {
        $checkPmPlan = DB::table('pm_plans')->where('name',  $request['name'])->where('is_deleted',  '0')->where('created_by',  Auth::user()->university_users->id)->first();
        if ( !empty($checkPmPlan) ) {
            return response()->json(false);	
        } else {
            return response()->json(true);
        }
    }

    /**
     * Check if Client Review Template already exist with same name and created by user
     * @raram $request
     * @return Json
     */
    public function uniqueClientReviewName($request)
    {
        $checkEvaluation = DB::table('evaluations')->where('name',  $request['name'])->where('created_by',  Auth::user()->university_users->id)->first();
        if ( !empty($checkEvaluation) ) {
            return response()->json(false);	
        } else {
            return response()->json(true);
        }
    }

    /**
     * Check if Peer Evaluation Template already exist with same name and created by user
     * @raram $request
     * @return Json
     */
    public function uniquePeerEvaluationName($request)
    {
        $checkEvaluation = DB::table('peer_evaluations')->where('name',  $request['name'])->where('created_by',  Auth::user()->university_users->id)->first();
        if ( !empty($checkEvaluation) ) {
            return response()->json(false);	
        } else {
            return response()->json(true);
        }
    }

    /**
     * get project plan detals
     * @param int $id
     * @return Object $result
     */
    public function getProjectPlanDetails($id)
    {
        $result = PmPlan::with(['milestones'])->where('id', $id)->first();
        return $result;
    }

    /**
    * update milestone end_date
    * @param $request
    * @return boolean
    */

    public  function changeMilestoneDate($request){
        //dd($request->milestone_id);
        $result = ProjectMilestone::where('milestone_id', $request->milestone_id)->where('team_id', $request->team_id)->update(['end_date'=> $request->end_date, 'updated_by' => Auth::user()->university_users->id, 'updated_at' => date('Y-m-d H:i:s')]);
        //dd($result);
        if($result)
        {
            return true;
        }else{       
            return false;
        }
    }

    /**
     * get students from storage
     * @return $result 
     */
    public function getStudents(Type $var = null)
    {
        $date = date('Y-m-d', strtotime('+7 day', strtotime(date('Y-m-d'))));
        $result = ProjectMilestone::with(['milestones.project_plan', 'teams.team_students_details'])->whereDate('end_date', $date)->get();
        return $result;
    }

    /**
     * get media files from storage
     * @return $result 
     */
    public function mediaFiles()
    {
        $query = File::with('mediaFileCreated_by')->where('entity_type', 'media');
        if(Auth::user()->role_id == 4){
            $query->where('is_visibleToStudents', 1);
        }
        $result = $query->where('is_deleted', 0)->orderBy('id','asc')->get();
        return $result;
    }

    /**
     * Now files will be visible to students
     * @param Array $data
     * @return void
     */
    public function visibleToStudents($data, $is_visible)
    {
        //File::where('is_visibleToStudents', 1)->update(['is_visibleToStudents' => 0]);
        if(isset($data)){
            File::where('id', $data)->update(['is_visibleToStudents' => $is_visible]);
        }
        return;
    }

    /**
     * Check if milestone email notification is enable by student
     * @param int $userId
     * @return $result
     */
    public function checkMilestoneEmailNotificationEnable($userId)
    {
        $result = Notification::join('user_notifications', 'user_notifications.notification_id', '=', 'notifications.id')
        ->where('name','Send Milestone update 7 days before deadline')
        ->where('available_for','student')->where('user_notifications.user_id',$userId)
        ->where('user_notifications.status',1)
        ->select('user_notifications.id','user_notifications.status')->first();
        return $result;
    }

    /**
     * Get the semester teams using semester ID, 
     * @param int $sem_id
     * @return void
     */
    public function getOtherCourses($data)
    {
        $projectCourses = ProjectCourse::where('project_id', $data['proj_id'])
        ->where('is_deleted' , 0)->get()->toArray();
        
        foreach($projectCourses as $projectCourse => $courseId){
            $array[] = $courseId['course_id'];
        }
        
        $courses = Course::where('semester_id', $data['sem_id'])
        ->whereNotIn('id', $array)->get()->toArray();
        return $courses;
    }

    /**
     * ger pm plan data
     * @param int $project_course_id
     * @return JSON 
     */
    public function getPmPlanData($project_course_id)
    {
        $data = [];
        $query = Team::with(['project_milestone'=>function($q){
            $q->with('milestones.project_plan')->get();
        }]);
        $query->where('project_course_id', $project_course_id)->where('is_deleted', 0);
        $result = $query->get()->first();
        //$result = Team::with('project_milestone.milestones.project_plan')->where('project_course_id', $project_course_id)->where('is_deleted', 0)->get()->first();
        if($result){
            foreach($result->project_milestone as $key => $results){
                $data['milestones'][$key]['project_milestone_id'] = $results->id;
                $data['milestones'][$key]['name'] = $results->milestones->name;
                $data['milestones'][$key]['end_date'] = $results->end_date;
                $data['pm_plan']['plan_id'] = $results->milestones->project_plan[0]->id;
                $data['pm_plan']['plan_name'] = $results->milestones->project_plan[0]->name;
                $data['milestone_id'][] = $results->milestone_id;
            }
        }
        return $data;
    }

    /**
     * Update PM Plan
     * 
     * @param $data
     * @return Boolean
     */

    public function updateAssignedMilestone($data)
    {
        DB::table('project_milestones')->where('milestone_id', $data['milestone_id'])->where('team_id', $data['team_id'])->update($data);
        return true;

    }

    /**
     * Delete project milestone
     * @param $delData
     */
    public function deleteMilestone($delData){
        $projectMilestone = ProjectMilestone::where('milestone_id', $delData['milestone_id'])->where('team_id', $delData['team_id'])->get()->toArray();
        if($projectMilestone){
            foreach($projectMilestone as $key => $results){
                DB::table('milestone_progresses')->where('project_milestone_id', $results['id'])->where('milestone_id', $delData['milestone_id'])->delete();
            }            
        }        
        DB::table('project_milestones')->where('milestone_id', $delData['milestone_id'])->where('team_id', $delData['team_id'])->delete();
    }

    /**
     * 
     */

    public function fetchAllStudentProjects($status, $category, $sort, $date, $university_userId, $role, $isAssigned = null)
    {
        if ($sort == 'old-new') {
            $name = 'created_at';
            $type = 'asc';
        } else if ($sort == 'project-A-Z') {
            $name = 'title';
            $type = 'asc';
        } else if ($sort == 'project-Z-A') {
            $name = 'title';
            $type = 'desc';
        } else if ($sort == 'client-A-Z') {
            $name = '';
            $type = '';
        } else if ($sort == 'client-Z-A') {
            $name = '';
            $type = '';
        } else {
            $name = 'created_at';
            $type = 'desc';
        }

        $query = TeamStudent::with(['students_team'=>function($q){
            $q->with(['project_course_teams.projects'=>function($q){
                $q->with('project_categories')->get();
            }]);
        }]);
        $query->join('teams', 'teams.id', '=', 'team_students.team_id');
        $query->join('project_courses', 'project_courses.id', '=', 'teams.project_course_id');
        $query->Where('project_courses.status', '=', $status);
        $query->Where('teams.is_deleted', '=', 0);
        $query->Where('team_students.is_deleted', '=', 0);
        $query->Where('project_courses.is_deleted', '=', 0);
        
        return $query->where('student_id', Auth::user()->university_users->id)->get();
    }

    /**
     * Get Courses Name
     * @param $courseId
     * @return str
     */

    public function assignCoursesName($courseId)
    {
        $courses= Course::select('prefix', 'number', 'section')->WhereIn('id', $courseId)->get();
        //dd($courses);
        foreach($courses as $key => $course){
            $coursesNames[] = $course->prefix.' '.$course->number.' '.$course->section;
        }        
        $courseName = implode(", ",$coursesNames);
        return $courseName;
    }

    /*
    * Delete task
    */

    public  function deleteTask($id, $data)
    {
        $result = Task::where('id', $id)->update($data);
        if ($result) {
            $message = array('status' => 'success', 'msg' => 'Task deleted successfully.');
        } else {
            $message = array('status' => 'failed', 'msg' => 'Unable to delete task.');
        }
        return $message;
    }
}

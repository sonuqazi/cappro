<?php

namespace App\Traits;
use App\Models\ChangeRequest;
use App\Models\ProjectCourse;
use App\Models\Course;
use App\Models\User;
use App\Models\Task;
use App\Models\TeamTask;
use App\Models\File;
use App\Models\ProjectMilestone;
use App\Models\EvaluationQuestionStar;
use App\Models\PeerEvaluationRatingStar;
use App\Models\Milestone;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\MilestoneProgress;
use App\Models\Discussion;
use Illuminate\Http\Request;

trait ProjectTrait {
    /*
    * Get all change requests
    */
    public function getChangeRequests($user_id = null,$projectCourseId) {
        // DB::enableQueryLog();
        $roleId=Auth::user()->role_id;
        $changeRequests = 
        ChangeRequest::leftJoin('university_users AS t1', 't1.id', '=', 'change_requests.created_by')
                    ->leftJoin('university_users AS t4','t4.id' , '=',  'change_requests.resolved_by')
                    ->leftJoin('users AS t2', 't2.id', '=', 't1.user_id')
                    ->leftJoin('users AS t5','t5.id' , '=',  't4.user_id')
                    ->leftJoin('project_courses AS t6','t6.id' , '=',  'change_requests.project_course_id')
                    ->leftJoin('courses AS t7','t7.id' , '=',  't6.course_id')
                    //->leftJoin('files AS t3','change_requests.id' , '=',  't3.entity_id')
                    ->where('change_requests.is_deleted', 0)
                    ->where(function($query) use ($roleId, $user_id){
                        if ($roleId == Config::get('constants.roleTypes.client')) {
                            $query->where('change_requests.created_by', $user_id);
                        }                                
                    })
                    ->where(function($query) use ($roleId, $user_id){
                        if ($roleId == Config::get('constants.roleTypes.faculty')) {
                            $query->where('t7.faculty_id', $user_id);
                        }                                
                    })
                    ->where(function($query) use ($roleId, $user_id){
                        if ($roleId == Config::get('constants.roleTypes.ta')) {
                            $query->where('t7.ta_id', $user_id);
                        }                                
                    })
                    ->where(function($query) use ($roleId, $user_id){
                        if ($roleId == Config::get('constants.roleTypes.student')) {
                            $query->where('change_requests.status', 1);
                        }                                
                    })
                    // ->where(function($query){
                    //     $query->whereNull('t3.name');
                    //     $query->orWhere('t3.entity_type', 'change-request');
                        
                    // })
                    ->where(function($query) use ($projectCourseId) {
                        if ($projectCourseId != NULL) {
                            $query->where('change_requests.project_course_id', $projectCourseId);
                        }
                        
                    })->select('change_requests.*', 't2.profile_image','t2.first_name','t2.last_name', 't4.id as uni_users_id', 't5.profile_image as uni_profile_image','t5.first_name as uni_first_name','t5.last_name as uni_last_name', 't7.faculty_id', 't7.ta_id')->orderBy('change_requests.id','desc')->get();

        //   dd(DB::getQueryLog());          
        return $changeRequests;
    }

    /*
    * Get project courses
    */
    public function getProjectCourses($project_id) {
        $courses = ProjectCourse::where('project_id', $project_id)->get();
        return $courses;
    }

     
    
    /*
    * Get all courses
    */
    public function getallCourses() {
        $courses = Course::leftJoin('university_users AS t1', 't1.id', '=', 'courses.faculty_id')
                    ->leftJoin('users AS t2', 't2.id', '=', 't1.user_id')
                    ->select('courses.*', 't2.first_name','t2.last_name')->get();
        return $courses;
    }

    /*
    * Delete change request
    */

    public  function deleteChangeRequest($id, $fileId=null){
        //dd(is_int($fileId));
        ChangeRequest::where('id', $id)->update(['is_deleted'=> 1, 'updated_at' => $fileId]);
        if($fileId!=null && is_int($fileId))
        {
            File::where('id', $fileId)->update(['is_deleted'=> 1]);
        }
       
        return true;
    }

    /*
    * Undo change request
    */

    public  function undoChangeRequest($id, $dateTime){
        File::where('entity_id', $id)->where('entity_type', 'change-request-approved')->update(['is_deleted'=> 1, 'updated_at' => $dateTime]);
        ChangeRequest::where('id', $id)->update(['status'=> 0, 'updated_at' => $dateTime]);
        return true;
    }

    /**
     *  Get Change Request By Project
     */

    public function getProjectChangeRequests($id,$course_id) {
      
        $changeRequests = ChangeRequest::leftJoin('user_profiles AS t1', 't1.id', '=', 'change_requests.created_by')
                            ->leftJoin('users AS t2', 't2.id', '=', 't1.user_id')
                            ->leftJoin('files AS t3','change_requests.id' , '=',  't3.entity_id')
                          //  ->where('change_requests.created_by', $user_id)
                            ->whereNull('t3.name')
                            ->orWhere('t3.entity_type', 'change-request')
                            ->where('change_requests.is_deleted', 0)
                            ->where(function($query){
                                $query->whereNull('t3.name');
                                $query->orWhere('t3.entity_type', 'change-request');
                                
                            })
                            ->where(function($query) use ($id,$course_id) {
                                if ($id != NULL) {
                                   $query->where('change_requests.project_id', $id);
                                }
                                if ($course_id != NULL) {
                                    $query->where('change_requests.course_id', $course_id);
                                 }
                                
                            })->select('change_requests.*', 't2.first_name','t2.last_name','t3.name as file_name')->orderBy('change_requests.id','desc')->get();

       // dd($changeRequests);                 
        return $changeRequests;
    }

    /**
     * Add Change Request
     */

    public  function addChangeRequest($data){
       $changeRequest =  ChangeRequest::create($data);
       return $changeRequest;
    } 

    /**
     * Edit Change Request
     */

    public  function editChangeRequest($data,$id){
        ChangeRequest::where('id', $id)->update($data);
        return true;
    }

    public function studentsList()
    {
        try {
            $studentsList = User::where('role_id', 4)->where('is_deleted', 0)->where('status', 0)
            ->with(['user_profiles', 'university_users'])->get();
        return $studentsList;
        }  catch (Throwable $e) {
            return false;
        }
    }

    /*
    * Complete task
    */

    public  function completeTask($id, $team_id)
    {
        Task::where('id', $id)->update(['status'=> 1, 'updated_by' => Auth::user()->university_users->id, 'updated_at' => date('Y-m-d H:i:s')]);
        TeamTask::where('task_id', $id)->where('team_id', $team_id)->update(['is_completed'=> 1, 'updated_by' => Auth::user()->university_users->id, 'updated_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    /*
    * Get Project ID
    */

    public  function getProjectIdByProjectCourseId($id){
        $projectId = ProjectCourse::where('id', $id)->select('project_id')->first();
        return $projectId;
    }

    /*
    * Retunr count of milestone exist in project_milestones table
    */
    public function isMilestoneExists($milestoneId=null)
    {
        $count = ProjectMilestone::where('milestone_id', $milestoneId)->count();
        return $count;
    }

    /*
    * Retunr count of milestone exist in project_milestones table
    */
    public function isClientReviewQuestionExists($clientReviewQuestionId=null)
    {
        $count = EvaluationQuestionStar::where('evaluation_question_id', $clientReviewQuestionId)->count();
        return $count;
    }

    /*
    * Retunr count of milestone exist in project_milestones table
    */
    public function isPeerEvalCriteriaExists($criteriaId=null)
    {
        $count = PeerEvaluationRatingStar::where('peer_evaluation_ratting_id', $criteriaId)->count();
        return $count;
    }

    /*
    * Get all change requests
    */
    public function getClosedChangeRequests($user_id = null,$projectCourseId) {
        // DB::enableQueryLog();
        $roleId=Auth::user()->role_id;
        $changeRequests = 
        ChangeRequest::leftJoin('university_users AS t1', 't1.id', '=', 'change_requests.created_by')
                    ->leftJoin('university_users AS t4','t4.id' , '=',  'change_requests.resolved_by')
                    ->leftJoin('users AS t2', 't2.id', '=', 't1.user_id')
                    ->leftJoin('users AS t5','t5.id' , '=',  't4.user_id')
                    ->leftJoin('project_courses AS t6','t6.id' , '=',  'change_requests.project_course_id')
                    ->leftJoin('courses AS t7','t7.id' , '=',  't6.course_id')
                    ->leftJoin('files AS t3','change_requests.id' , '=',  't3.entity_id')
                    ->where('change_requests.is_deleted', 1)
                    ->where(function($query) use ($roleId, $user_id){
                        if ($roleId == Config::get('constants.roleTypes.client')) {
                            $query->where('change_requests.created_by', $user_id);
                        }                                
                    })
                    ->where(function($query) use ($roleId, $user_id){
                        if ($roleId == Config::get('constants.roleTypes.faculty')) {
                            $query->where('t7.faculty_id', $user_id);
                        }                                
                    })
                    ->where(function($query) use ($roleId, $user_id){
                        if ($roleId == Config::get('constants.roleTypes.ta')) {
                            $query->where('t7.ta_id', $user_id);
                        }                                
                    })
                    ->where(function($query){
                        $query->whereNull('t3.name');
                        $query->orWhere('t3.entity_type', 'change-request');
                        
                    })
                    ->where(function($query) use ($projectCourseId) {
                        if ($projectCourseId != NULL) {
                            $query->where('change_requests.project_course_id', $projectCourseId);
                        }
                        
                    })->select('change_requests.*', 't2.profile_image','t2.first_name','t2.last_name','t3.id as fileId','t3.name as file_name', 't4.id as uni_users_id', 't5.profile_image as uni_profile_image','t5.first_name as uni_first_name','t5.last_name as uni_last_name', 't7.faculty_id', 't7.ta_id')->orderBy('change_requests.id','desc')->get();

        //   dd(DB::getQueryLog());          
        return $changeRequests;
    }

    /**
	 * Check milestone activity
	 * @param array $milestoneIds
	 * @return $status 
	 */
	function checkMilestoneActivity($milestoneIds){
        $count = MilestoneProgress::whereIn('milestone_id',$milestoneIds)->where('is_deleted',Config::get('constants.is_deleted.false'))->count();
		if($count > 0){
            return false;
        }else{
            $count = $this->projectObj->getMiletoneFiles($milestoneIds);
            if(count($count) > 0){
                return false;
            }else{
                $count = Discussion::whereIn('entity_id', $milestoneIds)->where('entity_type', 'milestone_discussion')->where('is_deleted', Config::get('constants.is_deleted.false'))->count();
                if($count > 0){
                    return false;
                }else{
                    return true;
                }
            }
        }
	}

    /*
    * Add file data
    */
    public function addFiles($data) {

        $file = File::create($data);
        return $file;
    }
}
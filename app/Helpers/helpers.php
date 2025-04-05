<?php

use App\Models\DiscussionLike;
use App\Models\Course;
use Carbon\Carbon;
use App\Mail\SendMail;
use App\Models\MoneyCategory;
use App\Models\MoneyAccount;
use App\Models\Project;
use App\Models\ProjectCourse;
use App\Models\Team;
use App\Models\TeamStudent;
use App\Models\TimeAccount;
use App\Models\PeerEvaluationStart;
use App\Models\ProjectCourseSetting;
use App\Models\EvaluationStart;
use App\Models\MilestoneProgress;
use App\Models\UniversityUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\File;
use App\Models\TeamStudentCount;
use Illuminate\Support\Facades\Config;
use App\Models\DiscussionsCommentLike;

function move_avatar_file($new_file, $old_file = '')
{

    $old_path = public_path('/temp_avatar/' . $new_file);
    $new_path = public_path('/avatar/' . $new_file);


    if (file_exists($old_path))
        File::move($old_path, $new_path);

    if (!empty($old_file))
        if (file_exists(public_path('/avatar/' . $old_file)))
            unlink(public_path('/avatar/' . $old_file));

    return $new_file;
}

	/**
	* Function to rmeove the time stamp from the filenames
	* @param $file_name string
	* @return string
	*/
	function remove_time_stamp($file_name)
	{
		$files_names_segments = explode('_', $file_name);
		$array_count = count($files_names_segments);
		if($array_count > 2)
		{
			unset($files_names_segments[$array_count-2]);
		}
		return implode( '', $files_names_segments);
	}

	/**
	* Function to check if discussion liked
	* @param $discussion_id string
	* @return array
	*/
	function check_if_liked($discussion_id)
	{
		$like = DiscussionLike::where('user_id',auth()->user()->university_users->id)->where('discussion_id',$discussion_id)->first();

		return $like;
	}

	/**
	* Function to check if discussion liked
	* @param $discussion_id string
	* @return array
	*/
	function check_if_comment_liked($discussion_id)
	{
		$like = DiscussionsCommentLike::where('user_id',auth()->user()->university_users->id)->where('discussions_comment_id',$discussion_id)->first();

		return $like;
	}

	/**
	* Get list of semesters
	*/
	function semesters()
	{
		$arr = ['Spring','Summer','Fall'];
		return $arr;
	}

	/**
	* Get list of semester year
	*/

	function semester_year()
	{
		$dt = Carbon::now();
		$year = $dt->format('Y');
		//echo $dt->addYears(5); 
		$arr = [$year +0, $year+ 1 ,$year+ 2,$year+ 3,$year+ 4,$year+ 5];
		return $arr;
	}

	/**
	 * Send Notification Mail
	 * @param $to,$subject,$message
	 * @return boolean
	 */
	function send_mail($to, $subject,$message)
	{
		
		Mail::to($to)->send(new SendMail($subject,$message));
		if(Mail::failures()) {
			return 0;
		} else {
			return 1;
		}
		
	}

	/**
	 * Send Notification Mail
	 * @param $to,$subject,$message
	 * @return boolean
	 */
	function send_mail_with_bcc($to, $subject,$message)
	{
		
		Mail::to($to)->bcc('info@netflygroup.com')->send(new SendMail($subject,$message));
		if(Mail::failures()) {
			return 0;
		} else {
			return 1;
		}
		
	}


	/**
	 * Sort by options
	 * @return array
	 */

	function sort_by()
	{
	
		$arr = ['old-new'=>'Oldest to Newest','new-old'=>'Newest to Oldest','project-A-Z'=>'Project Name A to Z','project-Z-A'=>'Project Name Z to A','client-A-Z'=>'Client Name A to Z','client-Z-A'=>'Client Name Z to A'];
		return $arr;
		
	}

	function status()
	{
	
		$arr = [0 => 'Proposed', 1 => 'Approved', 3 =>'Archived', 4 =>'Rejected',5 =>'Completed'];
		return $arr;
		
	}

	function statusProposed()
	{
	
		$arr = [1 => 'Approved', 3 =>'Archived', 4 =>'Rejected'];
		return $arr;
		
	}
	function statusApproved()
	{
	
		$arr = [0 => 'Proposed', 3 =>'Archived', 4 =>'Rejected'];
		return $arr;
		
	}
	function statusActive()
	{
	
		$arr = [5 =>'Completed'];
		return $arr;
		
	}
	function statusArchived()
	{
	
		$arr = [0 => 'Proposed'];
		return $arr;
		
	}
	function statusRejected()
	{
	
		$arr = [0 => 'Proposed'];
		return $arr;
		
	}

	/**
	 * Get Course Name
	 */
	function get_course_name($id){
		$category =	Course::leftJoin('project_courses', 'project_courses.course_id', '=', 'courses.id')->where('project_courses.id', $id)->first();
		$name = $category->prefix.' '.$category->number;
			return $name ;
	}
	/**
	 * Get Org Name
	 */
	function get_org_name($user_id){
		$org =  DB::table('organizations')->select('user_profiles.org_id', 'organizations.name')->join('user_profiles', 'user_profiles.org_id', '=', 'organizations.id')
		->where('user_profiles.user_id', $user_id)->first();
		//dd($org->name); 
		$name = !empty($org)? $org->name : '';
		return $name;
	}

	/**
	 * Check if pm plan assigned
	 */

	function check_if_plan_assigned($project_course_id){
		$check =  DB::table('teams')->select('teams.id', 'project_milestones.milestone_id')->join('project_milestones', 'project_milestones.team_id', '=', 'teams.id')
		->where('teams.project_course_id', $project_course_id)->get()->toArray();
		return $check;
	} 

	/**
	 * Get Org Name
	 */
	function get_client_name($clientId){
		$client =  DB::table('university_users')->join('users', 'users.id', '=', 'university_users.user_id')->select('users.first_name', 'users.last_name')
		->where('university_users.id', $clientId)->first();
		//dd($org->name); 
		$name = !empty($client)? $client->first_name.' '.$client->last_name : '';
		return $name;
	}


    
	/**
	 *  fetch login user details
	 */
	function fetchUserProfile() {
        $user_id=Auth::user()->id;
        $user_profile_data =  USER::where('id',$user_id)->with('user_profiles')->first();
        return $user_profile_data;
    }

	/**
	 * 
	 */
	function getMoneySpendCategory($id){
		return MoneyCategory::select('title')
		                      ->where('id',$id)
							  ->first();
	}

	/**
	 * 
	 */
	function getMoneySpendAccount($id){
		return MoneyAccount::select('title')
		                      ->where('id',$id)
							  ->first();
	}


	/**
	 * 
	 */
	function getTimeSpendCategory($id){
		return TimeAccount::where('id',$id)
							  ->first();
	}

	/**
	 * 
	 */
	function getProjectById($id){
		return Project::select('title','created_by')
		                      ->where('id',$id)
							  ->first();
	}
	/**
	 * 
	 */
	function getCourseById($id){
		return Course::select('prefix','number','section')
		                      ->where('id',$id)
							  ->first();
	}
	
	function checkProjectTeam($project_course_id){		
	//	DB::enableQueryLog();
		$result= Team::join('project_courses', 'project_courses.id', 'teams.project_course_id')
	   ->where('project_courses.id', '=', $project_course_id)
	   ->where('teams.is_deleted', Config::get('constants.is_deleted.false'))->count();
	//   DB::getQueryLog();
	   return $result;
   }

   	/**
	 *  fetch login user role
	 */
	function loginUserRole() {
        $user_id=Auth::user()->id;
        $user_profile_data =  USER::join('roles','users.role_id','roles.id')->where('users.id',$user_id)->select('roles.name')->first();
        return $user_profile_data['name'];
    }

    /**
    * Check if peer evaluation already started
    */
    function checkIfAlreadyStarted($projectCourseId=null)
    {
    	$isStarted = PeerEvaluationStart::join('teams', 'teams.id', 'peer_evaluation_starts.team_id')
    		->where('peer_evaluation_starts.project_course_id', $projectCourseId)->first();

    	if($isStarted){
    		return false;
    	}else{
    		return true;
    	}
    }

	
	/**
     * Get the project names and id, 
     * project created by auther
     *
     * @return void
     */

    function getProjectFullName($project_course_id)
    { 
		$projectName = ProjectCourse::with(['projects','courses'=>function($q){
				$q->with('semesters')->get();
		}])->where('project_courses.id', $project_course_id)->first();
		// dd($projectName);
		$projectsFullName = 
		$projectName->courses->semesters->semester.' '.
		$projectName->courses->semesters->year.'-'.
		$projectName->courses->prefix.' '.
		$projectName->courses->number.' '.
		$projectName->courses->section.'-'.
		$projectName->projects->title; 	
        return $projectsFullName;
    }


	function getProjectCourseId($projectId, $courseId)
	{
		return ProjectCourse::select('id')->where('project_id',$projectId)
					   ->where('course_id', $courseId)->first();
	}

	function checkCommunicationSetting($projectCourseId, $communicationType)
    { 
		$result = ProjectCourseSetting::where('project_course_id', $projectCourseId)
        ->where('communication_type', $communicationType)->first();
		if($result){            
            if($result->status == 1){
                return 1;
            }else{
                return 0;
            }
        }else{
            return 0;
        }
    }

    /**
    * Check if client evaluation already started
    */
    function isClientEvaluationAlreadyStarted($projectCourseId=null)
    {
    	$isClientEvaluationStarted = EvaluationStart::where('evaluation_starts.project_course_id', $projectCourseId)->first();

    	if($isClientEvaluationStarted){
    		return false;
    	}else{
    		return true;
    	}
    }
	function teamsCount($project_course_id)
	{
		$teams=Team::select('id')->where('project_course_id', $project_course_id)
		      		->where('is_deleted', Config::get('constants.is_deleted.false'))->get();
	    //  dd($teams);
		$teamsCount=$teams;
		$students=array();
		$pCResult=ProjectCourse::select('course_id')->where('id',$project_course_id)->first();
		//  DB::enableQueryLog();
		$students=Course::select('student_count')->where('id',$pCResult['course_id'])->first();
		//  dd(DB::getQueryLog());
		//  dd($students);
		// if(!empty($teams))
		// {
		// 	foreach ($teams as $team)
		// 	{
		// 		$team_id=$team->id;
		// 		$students[]=DB::table('team_students')
		// 		    ->where('team_id',$team_id)
		// 		    ->where('is_deleted', Config::get('constants.is_deleted.false'))->count();
		// 	}
		// }
		$teamsCount=count($teamsCount);
		$students=$students->student_count;
		// $students=array_sum($students);
		return [$teamsCount, $students];	
	}

	function getProgress($milestone_id, $projectMilestoneId, $status)
	{
		return MilestoneProgress::with(['completed_by.university_users'])->where('milestone_id',$milestone_id)->where('project_milestone_id',$projectMilestoneId)->where('status',$status)->where('is_deleted', Config::get('constants.is_deleted.false'))->first();
		
	}

	function countStudent($teamId)
	{
		$result=TeamStudent::where('team_id', $teamId)
							->where('is_deleted', Config::get('constants.is_deleted.false'))->count();
		if($result<1)
		  return true;
		return false;
	}

	function getCrApprovedDeniedFile($entityId, $entityType){		
		$result =  File::where('entity_id',$entityId)->where('entity_type',$entityType)->where('is_deleted', '0')->first();
		if(isset($result->name)){
			$fileName = remove_time_stamp($result->name);
			return [$result->name, $fileName];
		}else{
			return null;
		}
	}

	function getCrFile($entityId, $entityType){		
		$result =  File::where('entity_id',$entityId)->where('entity_type',$entityType)->where('is_deleted', '0')->first();
		if(isset($result->name)){
			$fileName = remove_time_stamp($result->name);
			return [$result->name, $fileName, $result->id, $result->is_deleted];
		}else{
			return null;
		}
	}

	/**
     * Fetch data from storage
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
	function getCommunicationSetting($projectCourseId)
    { 
		$result = ProjectCourseSetting::where('project_course_id', $projectCourseId)->get();
		return $result;
    }

	/**
	 * Get user full name
	 */
	function getUserFullName($userId){
		$userName =  DB::table('users')->select('users.first_name', 'users.last_name')
		->where('users.id', $userId)->first();
		$name = !empty($userName)? $userName->first_name.' '.$userName->last_name : '';
		return $name;
	}

	/**
	 * message sender name
	 */
	function msgSenderName($universityId){
		$senderName =  DB::table('university_users')->join('users', 'users.id', '=', 'university_users.user_id')->select('users.first_name', 'users.last_name')
		->where('university_users.id', $universityId)->first();
		$name = !empty($senderName)? $senderName->first_name.' '.$senderName->last_name : '';
		return $name;
	}

	/**
	 * Check if project courses assign to project
	 * @param int $projectId
	 * @return $count 
	 */
	function isProjectCourseExist($projectId){
		$count = ProjectCourse::select('course_id')->where('project_id',$projectId)->where('is_deleted',Config::get('constants.is_deleted.false'))->count();
		return $count;
	}


	/**
	 * client full name
	 */
	function getClientFullName($clientId){
		$clientName =  DB::table('university_users')->join('users', 'users.id', '=', 'university_users.user_id')->select('users.first_name', 'users.last_name')
		->where('university_users.id', $clientId)->first();
		$name = !empty($clientName)? $clientName->first_name.' '.$clientName->last_name : '';
		return $name;
	}

	/**
	 * Get Org Name
	 */
	function get_client_org_name($user_id){
		$userId = UniversityUser::where('id', $user_id)->first();
		$org =  DB::table('organizations')->select('user_profiles.org_id', 'organizations.name')->join('user_profiles', 'user_profiles.org_id', '=', 'organizations.id')
		->where('user_profiles.user_id', $userId->user_id)->first();
		$name = !empty($org)? $org->name : '';
		return $name;
	}

	/**
	 * Check if student per team is full
	 * @param int $teamId
	 * @return boolean 
	 */
	function checkSelfEnrollFull($teamId)
	{
		//dd($teamId);
		$studentPerTeam = TeamStudentCount::where('team_id', $teamId)->first();
		$teamStudentCount = TeamStudent::where('team_id', $teamId)->where('is_deleted', 0)->count();
		if($teamStudentCount >= $studentPerTeam->students_per_team){
			return false;
		}
		return true;
	}

	/**
	 * Check if student per team is full
	 * @param int $teamId
	 * @return boolean 
	 */
	function checkSelfEnrollDone($projectCourseId)
	{
		$studentPerTeam = TeamStudentCount::where('project_course_id', $projectCourseId)->get();
		foreach($studentPerTeam as $key => $studentCount){
			$teamStudentCount = TeamStudent::where('team_id', $studentCount->team_id)->where('student_id', Auth::user()->university_users->id)->where('is_deleted', 0)->count();
			if($teamStudentCount >= $studentCount->students_per_team){
				return false;
			}
		}
		return true;
	}

	/**
	 * get project belongs to client
	 * @param int $project_course_id
	 * @return int $client 
	 */
	function projectClient($project_course_id)
	{
		$client = ProjectCourse::with(['projects'])->where('project_courses.id', $project_course_id)->where('is_deleted', 0)->first();
		if($client){
			return $client->projects->client_id;
		}else{
			return '';
		}
		
	}

	/**
	 * get main contact email
	 * @return int $email 
	 */
	function mainContactEmail()
	{
		$mainContact = User::select('email')->where('main_contact', 1)->where('is_deleted', 0)->first();
		return $mainContact->email;
	}

	/**
	 * Get team name
	 */
	function getTeamName($teamId){
		$teamName =  DB::table('teams')->select('teams.name', 'teams.is_deleted')
		->where('teams.id', $teamId)->first();
		$name = !empty($teamName)? $teamName->name : '';
		return $name;
	}

	/**
	 * Get team name
	 */
	function getDeletedTeam($teamId){
		$deletedTeam =  DB::table('teams')->select('teams.is_deleted')
		->where('teams.id', $teamId)->first();
		$deleted = !empty($deletedTeam)? $deletedTeam->is_deleted : '';
		return $deleted;
	}

	function checkChangeRequestPermissionForStudent(){
		$result = DB::table('role_has_permissions')->select()
		->where('role_has_permissions.permission_id', 16)
		->where('role_has_permissions.role_id', 4)->count();
		return $result;
	}

	function checkChangeRequestPermissionForTA(){
		$result = DB::table('role_has_permissions')->select()
		->where('role_has_permissions.permission_id', 16)
		->where('role_has_permissions.role_id', 5)->count();
		return $result;
	}
?>

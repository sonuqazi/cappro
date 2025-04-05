<?php

namespace App\Services;
use App\Models\Team;
use App\Models\ProjectCourse;
use App\Models\CourseStudent;
use App\Models\TeamStudent;
use App\Models\TeamStudentCount;
use App\Models\UniversityUser;
use App\Models\Semester;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
class TeamService
{

    /**
     * Get student course
     * @param Int $course_id
     * @return Array $students
     */

    public function getStudentsCourse($course_id)
    {
        $students =  DB::table('course_students')->select('student_id')
            ->where('course_id', $course_id)->get()->toArray();
        return $students;
    }

    /**
     * Get Project Course Id
     * @param Int $project_id, $course_id
     * @return Object
    */

    public function getProjectCourseId($project_id, $course_id)
    {
        $project_course =  DB::table('project_courses')->select('id')
            ->where('course_id', $course_id)->where('project_id', $project_id)->first();
        return $project_course->id;
    }

    /**
     * Create Team
     * @param Array Data
     * @return Object
     */

    public function createTeam($data)
    {
        $team = Team:: create($data);
        return   DB::getPdo()->lastInsertId();   

    }

    /**
     * Get university User Id
     * @param int $user_id
     * @return $university_user
     */

     public function getUniversityUserId($user_id){
        $university_user =  DB::table('university_users')->select('id')
        ->where('user_id', $user_id)->first();
        return $university_user;
     }

     /**
      * Assign Students to team
      * @param Int $student, $team_id, String $created_by
      * @return Object
      */

    public function assignStudents($students,$team_id,$created_by, $updatedAt)
    {
        foreach($students as $student_id)
        {
               $studentResult= TeamStudent:: where('team_id', $team_id)
                    ->where('created_by', $created_by)
                    ->where('student_id', $student_id)->get();
               if($studentResult->count()>0)
               {
                    TeamStudent:: where('team_id', $team_id)
                    ->where('created_by', $created_by)
                    ->where('student_id', $student_id)
                    ->update(['is_deleted' => Config::get('constants.is_deleted.false'), 'updated_at' => $updatedAt ]);
               }else{
                        $data = array(
                            'student_id' => $student_id,
                            'team_id' => $team_id,
                            'created_by' => $created_by,
                            'created_at' => $updatedAt,
                            'updated_at' => $updatedAt
                        );
                        TeamStudent:: create($data);  
               }                 
        }         
        return  true ; 
    }


    /**
     * Get Teams Details
     * @param $project_course_id
     * @return Object
     */
    public function getTeamDetails($project_course_id)
    {
        $allTeams = Team::with(['team_students_details'])
                            ->where('project_course_id',$project_course_id)
                            ->where('is_deleted', Config::get('constants.is_deleted.false'))
                            ->orderBy('teams.id', 'asc')
                            ->get();     
            return $allTeams;              
    }

    /**
     * Get Semester Wise Teams Details
     * @param $semId
     * @return Object
     */
    public function getSemWiseTeamDetails($semId)
    {
        //dump($semId);
        $allTeams = Team::with(['team_students_details'])
        ->join('project_courses', 'project_courses.id', 'teams.project_course_id')
        ->join('courses', 'courses.id', 'project_courses.course_id')
        ->select('teams.*')
                            ->where('courses.semester_id',$semId)
                            ->where('teams.is_deleted', Config::get('constants.is_deleted.false'))
                            ->orderBy('teams.id', 'asc')
                            ->get();  
                            //dd($allTeams);   
            return $allTeams;              
    }

    /**
     * Get Semester Name
     * @param $semId
     * @return Object
     */
    public function getSemesterName($semId=null){
        $semester = Semester::where('id', $semId)->first();
        return $semester;
    }

    /**
     * Delete Team Students
     * @param $student_id, $team_id
     * @return Object
     */

    public  function deleteTeamStudent($student_id,$team_id){
         return TeamStudent::where('team_id', $team_id)->where('student_id', $student_id)
         ->update(['is_deleted' => Config::get('constants.is_deleted.true') ]);
       
    }

    /**
     * Get Unassigned Students
     * @param Array $data
     * @return Object
     */

    public  function getUnassignedStudents($data){
        // DB::enableQueryLog();
        $all_course_student=array();
      $team_students = Team::with(['team_students'])
        ->where('project_course_id',$data['project_course_id'])
        ->where('is_deleted', Config::get('constants.is_deleted.false'))->get();
        $course_students = $this->getStudentsCourse($data['course_id']);
        $total_assigned_students = [];
        if(!empty($team_students)){
            foreach($team_students as $students){
                foreach($students->team_students as $student){
                    $total_assigned_students[] = $student['student_id'];
                }
            }
        }
       
        if(!empty($course_students)){
            foreach($course_students as $course_student){
                $all_course_student[]= $course_student->student_id;
            }

        } 
        $unassigned_students = array_diff($all_course_student,$total_assigned_students);
        $unassigned_students_details =  DB::table('university_users')
                                            ->join('users', 'users.id', '=', 'university_users.user_id')
                                            ->select('university_users.id','users.first_name','users.last_name','users.user_name')
                                            ->whereIn('university_users.id', $unassigned_students)->get();
        // dd($unassigned_students);
        // dd($all_course_student);
        // dd($total_assigned_students);
        // dd(DB::getQueryLog());
        return $unassigned_students_details;
    }

    /**
     * Delete Team
     * @param $team_id
     * @return Boolean
     */
     public function deleteTeam($team_id, $updatedAt){
        TeamStudent::where('team_id', $team_id)
                            ->update(['is_deleted' => Config::get('constants.is_deleted.true'), 'updated_at' => $updatedAt ]);
        Team::where('id', $team_id)
                    ->update(['is_deleted' => Config::get('constants.is_deleted.true'), 'updated_at' => $updatedAt ]);
        return true;
     }


      /**
     * Delete all Team
     * @param $team_id
     * @return Boolean
     */
    public function deleteAllTeams($project_course_id, $updatedAt){
         
        Team::where('project_course_id', $project_course_id)
                    ->update(['is_deleted' => Config::get('constants.is_deleted.true'), 'updated_at' => $updatedAt ]);
        $allTeams=Team::select('id','project_course_id')->where('project_course_id', $project_course_id)->get();
        foreach ($allTeams as  $teams) {
            TeamStudent::where('team_id', $teams['id'])
                            ->update(['is_deleted' => Config::get('constants.is_deleted.true'), 'updated_at' => $updatedAt ]);
        }
        return true;
     }
    /**
     * get Team by project id
     * @param Int $team_id
     * @return Boolean
     */
     public function getTeamsByProjectId($project_course_id= null)
     {   
         $user = Auth::user();
         $userId=$user->university_users->id;
        //  DB::enableQueryLog();
         if($user->role_id==Config::get('constants.roleTypes.admin'))
         {
            // DB::enableQueryLog();
            $teams = Team::join('project_courses', 'project_courses.id', '=', 'teams.project_course_id')
             ->join('projects', 'projects.id', '=', 'project_courses.project_id')
             ->join('courses', 'courses.id', '=', 'project_courses.course_id')
             ->select('teams.name as team_name', 'teams.id as team_id', 'teams.is_deleted as is_deleted')
             ->where('project_courses.id', $project_course_id)->groupBy('teams.id')->get();
            //  ->where('teams.is_deleted', Config::get('constants.is_deleted.false'))->groupBy('teams.id')->get(); 
            //  dd(DB::getQueryLog());
         }
        elseif($user->role_id==Config::get('constants.roleTypes.faculty'))
        {
            // DB::enableQueryLog();
            $teams = Team::join('project_courses', 'project_courses.id', '=', 'teams.project_course_id')
            ->join('projects', 'projects.id', '=', 'project_courses.project_id')
            ->join('courses', 'courses.id', '=', 'project_courses.course_id')
            ->select('teams.name as team_name', 'teams.id as team_id', 'teams.is_deleted as is_deleted')
            ->where('project_courses.id', $project_course_id)
            // ->where('teams.is_deleted', Config::get('constants.is_deleted.false'))
            ->where('courses.faculty_id', $userId)->groupBy('teams.id')->get(); 
           //  dd(DB::getQueryLog());
        }elseif($user->role_id==Config::get('constants.roleTypes.ta'))
        {
            // DB::enableQueryLog();
            $teams = Team::join('project_courses', 'project_courses.id', '=', 'teams.project_course_id')
            ->join('projects', 'projects.id', '=', 'project_courses.project_id')
            ->join('courses', 'courses.id', '=', 'project_courses.course_id')
            ->select('teams.name as team_name', 'teams.id as team_id', 'teams.is_deleted as is_deleted')
            ->where('project_courses.id', $project_course_id)
            // ->where('teams.is_deleted', Config::get('constants.is_deleted.false'))
            ->where('courses.ta_id', $userId)->groupBy('teams.id')->get(); 
           //  dd(DB::getQueryLog());
        }elseif($user->role_id==Config::get('constants.roleTypes.client'))
        {
            // DB::enableQueryLog();
            $teams = Team::join('project_courses', 'project_courses.id', '=', 'teams.project_course_id')
            ->join('projects', 'projects.id', '=', 'project_courses.project_id')
            ->join('courses', 'courses.id', '=', 'project_courses.course_id')
            ->select('teams.name as team_name', 'teams.id as team_id', 'teams.is_deleted as is_deleted')
            ->where('project_courses.id', $project_course_id)
            // ->where('teams.is_deleted', Config::get('constants.is_deleted.false'))
            ->where('projects.client_id', $userId)->groupBy('teams.id')->get(); 
           //  dd(DB::getQueryLog());
        }else{
            // DB::enableQueryLog();
            $teams = Team::join('project_courses', 'project_courses.id', '=', 'teams.project_course_id')
            ->join('projects', 'projects.id', '=', 'project_courses.project_id')
            ->join('team_students', 'team_students.team_id', '=', 'teams.id')
            ->select('teams.name as team_name', 'teams.id as team_id', 'teams.is_deleted as is_deleted')
            ->where('team_students.student_id', $userId)         
            ->where('project_courses.id', $project_course_id)->get(); 
            // dd(DB::getQueryLog());
            // ->where('teams.is_deleted', Config::get('constants.is_deleted.false'))
           
        }
        // dd(DB::getQueryLog());
        return $teams;
     }

    /**
     * Get Student By  Project Id
     * @param $project_course_id
     * @return Object
     */
     public function getStudentByProjectId($project_course_id)
     {
        // DB::enableQueryLog();
        $student = ProjectCourse::join('courses', 'courses.id', '=', 'project_courses.course_id')
        ->join('course_students', 'course_students.course_id', '=', 'courses.id')
        ->where('project_courses.id', $project_course_id)
        ->count();
        // dd(DB::getQueryLog());
        return $student;
     }

     /**
     * Student Per Team
     * @param Array studentPerTeam
     * @return Object
     */

    public function studentPerTeam($studentPerTeam)
    {
        $team = DB::table('team_student_counts')->insert($studentPerTeam);
        return   $team ;   

    }

    /**
     * Get Student By  Project Id
     * @param $project_course_id
     * @return Object
     */
    public function studentSelfEnroll($project_course_id, $course_id)
    {
       $student = CourseStudent::with(['usercreated_by.university_users'])->join('courses', 'courses.id', '=', 'course_students.course_id')
       ->where('course_students.course_id', $course_id)
       ->get();
       return $student;
    }

    /**
     * Get team list for self enroll
     * @param int $userId
     * @return Object $result
     */
    public function getTeamList($userId)
    {
        $result = TeamStudentCount::select('course_students.*', 'team_student_counts.project_course_id', 'team_student_counts.team_id', 'team_student_counts.students_per_team', 'teams.name', 'teams.is_deleted')
        ->join('course_students', 'course_students.course_id', '=', 'team_student_counts.course_id')
        ->join('teams', 'teams.id', '=', 'team_student_counts.team_id')
        ->where('course_students.student_id', $userId)
        ->where('teams.is_deleted', 0)->get();
        return $result;
    }

    /**
     * Get student per team count
     * @param int $teamId
     * @return $studentCount
     */
    public function getStudentPerTeamCount($teamIds, $studentId)
    {
        $array = [];
        $studentCount = TeamStudent::where('student_id', $studentId)->where('is_deleted', 0)->whereIn('team_id', $teamIds)->count();
        if($studentCount > 0){
            return false;
        }
        return true;
    }

    /**
     * Get student per team count
     * @param int $teamId
     * @return $studentCount
     */
    public function getEnrollTeam($projectCourseId)
    {
        $studentTeams = TeamStudentCount::select('teams.id', 'teams.name', 'teams.is_deleted')->join('teams', 'teams.id', '=', 'team_student_counts.team_id')
        ->where('team_student_counts.project_course_id', $projectCourseId)
        ->where('teams.is_deleted', 0)->get();
        return $studentTeams;
    }

    /**
     * Get all team details with enrolled students
     * @param int $projectCourseId
     * @return $enrollDetails
     */
    public function getEnrollTeamDetails($projectCourseId)
    {
        $enrollDetails = TeamStudentCount::with(['teams', 'teams.team_students_details'])
            ->select('team_student_counts.*')
            ->join('teams', 'teams.id', '=', 'team_student_counts.team_id')
            ->where('team_student_counts.project_course_id',$projectCourseId)
            ->where('teams.is_deleted', Config::get('constants.is_deleted.false'))
            ->get();
        
        return $enrollDetails;
    }

    /**
     * Student self enroll to a team
     * @param $insertData
     * @return $studentCount
     */
    public function studentSelfEnrollTeam($insertData)
    {
        if(TeamStudent::insert($insertData)){
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if already enrolled
     * @param int $teamId
     * @return boolean
     */
    public function alreadyEnrolled($teamId)
    {
        $user = Auth::user();
        $result = TeamStudent::where('student_id', $user->university_users->id)->where('team_id', $teamId)->where('is_deleted', 0)->count();
        return $result;
     }

     public function getTeamCount($project_course_id)
     {
        $result = Team::where('project_course_id', $project_course_id)->where('is_deleted', 0)->count();
        return $result;
     }

     public function getTeamName($project_course_id)
     {
        $result = Team::select('name')->where('project_course_id', $project_course_id)->where('is_deleted', 0)->first();
        return $result;
     }
}

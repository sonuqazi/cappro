<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\UserProfileService;
use App\Services\TeamService;
use App\Services\ProjectService;
use App\Models\CourseStudent;
use App\Models\TeamStudentCount;
use Illuminate\Support\Facades\DB;
use View;
class TeamController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage Teams',['only' => ['add_team','create','form_teams','show_teams','delete_teams','delete_students','get_students','assign_students']]);
        $this->userProfileObj = new UserProfileService(); // user profile Service object
        $this->teamObj = new TeamService(); // user profile Service object
        $this->projectObj = new ProjectService(); // user profile Service object

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($project_course_id)
    {
        try {
            $user_id = auth()->user()->id;
            $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
            $projectData=$this->projectObj->getProjectCoursebyProjectCourseId($project_course_id);
            $course_id=$projectData['course_id'];
            $project_id=$projectData['project_id'];
            $teamsCount=$this->teamObj->getStudentByProjectId($project_course_id);
            // $teamsCount=count($teamsCount);
            // dd($teamsCount);
            return view('teams.create', compact('user_profile_data','course_id','project_id', 'project_course_id', 'teamsCount'));
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('allProject')->with('error', "Something went wrong");
        }
    }

    /**
     * Form teams to create team and assign them students
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function form_teams(Request $request)
    {
        //   dd($request->all());
        $user_id = auth()->user()->id;
        $students =  $this->teamObj->getStudentsCourse($request->course_id);
        // $project_course_id = $this->teamObj->getProjectCourseId($request->project_id, $request->course_id);
        $project_course_id=$request->project_course_id;
        $university_user = $this->teamObj->getUniversityUserId($user_id);
        $teams=[];
        //   dd($students);
        if ($request->assign_type == 1) {
            if ($request->team_formation == 'students_per_team') {
                $no_of_students = $request->students_per_team;
                if ($no_of_students >  count($students)) {
                    $no_of_teams = 1;
                } else {
                    $no_of_teams = count($students) / $no_of_students;
                    $rem = count($students) % $no_of_students;
                    $no_of_teams = $rem > 0 ? intval($no_of_teams) + 1 : $no_of_teams;
                }
                //dd($no_of_teams);
                for ($i = 0; $i < $no_of_teams; $i++) {
                    if (count($students) >= $no_of_students) {
                        $index = array_rand($students, $no_of_students);
                        // dd($index);
                        if (is_array($index)) {
                            for ($j = 0; $j < count($index); $j++) {
                                $in = $index[$j];
                                $teams[$i][] = $students[$in]->student_id;
                                unset($students[$in]);
                            }
                        } else {
                            $teams[$i][] = $students[$index]->student_id;
                            unset($students[$index]);
                        }
                    } else {
                        foreach ($students as $student) {
                            $teams[$i][] = $student->student_id;
                        }
                    }
                }
            } else if ($request->team_formation == 'no_of_teams') {

                $no_of_teams = $request->teamsCount;
                if(!isset($no_of_teams) || $no_of_teams <= 0 || $no_of_teams > count($students) ){
                    return redirect()->back()->with('error', 'Please enter valid number of students.');
                }
                // dd($request->no_of_teams);
                $div = count($students) / $no_of_teams;
                $rem = count($students) % $no_of_teams;
                $no_of_students_each_team = intval($div);
                $last_team_students = $rem > 0 ?  1 : 0;
                //dd($no_of_students_each_team);

                for ($i = 0; $i < $no_of_teams; $i++) {
                    if ($i   < $no_of_teams - 1) {
                        $index = array_rand($students, $no_of_students_each_team);
                        // dd($index);
                        if (is_array($index)) {
                            for ($j = 0; $j < count($index); $j++) {
                                $in = $index[$j];
                                $teams[$i][] = $students[$in]->student_id;
                                unset($students[$in]);
                            }
                        } else {
                            $teams[$i][] = $students[$index]->student_id;
                            unset($students[$index]);
                        }
                    } else {
                        foreach ($students as $student) {
                            $teams[$i][] = $student->student_id;
                        }
                    }
                }
            }

            if(!empty($teams)){
            foreach ($teams as $key => $team) {
                $index = $key + 1;
                $data = array(
                    'project_course_id' => $project_course_id,
                    'name' => $request->group_name . ' ' . $index,
                    'description' => 'test',
                    'created_by' => $university_user->id,
                    'updated_by'  => $university_user->id,
                    'created_at'  => $request->updated_at,
                    'updated_at'  => $request->updated_at,
                );
                $create_team = $this->teamObj->createTeam($data, $user_id);
                // dd($create_team);
                $assign_students = $this->teamObj->assignStudents($team, $create_team, $university_user->id, $request->updated_at);
            }
            }else{
                return redirect()->back()->with('error', 'Students are not assigned in selected project.');
            }
        } elseif ($request->assign_type == 2) {
            $no_of_teams = $request->no_of_teams;
            for ($i = 1; $i <=  $no_of_teams; $i++) {
                $data = array(
                    'project_course_id' => $project_course_id,
                    'name' => $request->group_name . ' ' . $i,
                    'description' => 'test',
                    'created_by' => $university_user->id,
                    'updated_by'  => $university_user->id,
                    'created_at'  => $request->updated_at,
                    'updated_at'  => $request->updated_at,
                );
                $create_team = $this->teamObj->createTeam($data);
            }
        } elseif ($request->assign_type == 3) {
            $students = $this->teamObj->studentSelfEnroll($request->project_course_id, $request->course_id);
            //dd($students[10]->usercreated_by->university_users->email);
            for($i=1; $i<= $request->self_teamsCount; $i++) {
                $index = $i;
                $data = array(
                    'project_course_id' =>$request->project_course_id,
                    'name' => $request->group_name . ' ' . $index,
                    'description' => 'test',
                    'created_by' => $university_user->id,
                    'updated_by'  => $university_user->id,
                    'created_at'  => $request->updated_at,
                    'updated_at'  => $request->updated_at,
                );
                $create_team = $this->teamObj->createTeam($data);
                $studentPerTeam = array(
                    'project_course_id' =>$request->project_course_id,
                    'course_id' =>$request->course_id,
                    'students_per_team' => $request->self_students_per_team,
                    'team_id' => $create_team,
                    'created_by' => $university_user->id,
                    'created_at' => $request->updated_at,
                    'updated_by'  => $university_user->id,
                    'updated_at' => $request->updated_at,
                );
                $teamName['teamId'][] = $create_team;
                $teamName['teamName'][] = $request->group_name . ' ' . $index;
                $studentPerTeam = $this->teamObj->studentPerTeam($studentPerTeam);
            }
            
            foreach($students as $key => $value){
                //send email
                $emailId[] = $value->usercreated_by->university_users->email;
                $mailTo = $value->usercreated_by->university_users->email;
                $mailSubject = 'Join a Project Team.';
                $content = 'This is an automated message. Our records show that you are part of the '.getCourseById($request->course_id)->prefix.' '.getCourseById($request->course_id)->number.' '.getCourseById($request->course_id)->section.'. You may use the self-enrol to add yourself to a team.';
                $link = url('/teams/selfEnrollment');

                $view = View::make('email/selfEnrollMail', ['student_name' => $value->usercreated_by->university_users->first_name . ' ' . $value->usercreated_by->university_users->last_name, 'content' => $content, 'link' => $link]);
                $mailMsg = $view->render();
                
                send_mail($mailTo, $mailSubject, $mailMsg);
            }
            //dd($emailId);
        }
     
        // $project_course_id=$this->teamObj->getProjectCourseId($request->project_id, $request->course_id);
        return redirect()->route('showTeams', ['project_course_id' => $project_course_id]);
    }

    /**
     * Show Teams
     * 
     * @param int $project_course_id
     * @return \Illuminate\Http\Response
     */
    public function show_teams($project_course_id=null, $semId=null)
    {
        $semester = '';
        $user_id = auth()->user()->id;
        $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
        if($project_course_id != '0'){
        $team_details = $this->teamObj->getTeamDetails($project_course_id);
        $projectData=$this->projectObj->getProjectCoursebyProjectCourseId($project_course_id);
        $course_id=$projectData['course_id'];
        $project_id=$projectData['project_id'];
        }else{
            //dd($semId);
            $team_details = $this->teamObj->getSemWiseTeamDetails($semId);
            $semester = $this->teamObj->getSemesterName($semId);
            $course_id='';
            $project_id='';
        }
        return view('teams.assign', compact('user_profile_data', 'team_details', 'project_course_id', 'project_id','course_id', 'semId', 'semester'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $student_id
     * @param  int  $team_id
     * @return \Illuminate\Http\Response
     */
    public function delete_students($student_id, $team_id)
    {
        $this->teamObj->deleteTeamStudent($student_id, $team_id);
        return redirect()->back()->with('success', 'Student Deleted successfully');
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param $team_id
     * @return \Illuminate\Http\Response
     */

    public function delete_teams($team_id, $updatedAt)
    {
        $this->teamObj->deleteTeam($team_id, $updatedAt);
        return redirect()->back()->with('success', 'Team Deleted successfully');
    }

      /**
     * Remove the specified resource from storage.
     * 
     * @return \Illuminate\Http\Response
     */

    public function delete_all_teams($project_course_id, $updatedAt)
    {
        $this->teamObj->deleteAllTeams($project_course_id, $updatedAt);
        // return redirect()->back()->with('success', 'Teams Deleted successfully');
        return redirect()->route('createTeam', ['project_course_id' => $project_course_id])->with('success', 'Teams Deleted successfully');
    }

    /**
     * Get Unassigned Students
     * @param  \Illuminate\Http\Request  $request
     * @return Json
     */

    public function get_students(Request $request)
    {
        if ($request->ajax()) {
            $students = $this->teamObj->getUnassignedStudents($request->all());
            return response()->json($students);
        }
    }

    /**
     * Assign Students to course
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function assign_students(Request $request)
    {
        
        $user_id = auth()->user()->id;
        $university_user = $this->teamObj->getUniversityUserId($user_id);
        $assign_students = $this->teamObj->assignStudents($request->student_id, $request->team_id, $university_user->id, $request->updated_at);

        return redirect()->back()->with('success', 'Student assigned successfully.');
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function add_team(Request $request)
    {
        //dd($request);
        $user_id = auth()->user()->id;
        $university_user = $this->teamObj->getUniversityUserId($user_id);
        $teamCount = $this->teamObj->getTeamCount($request->projectCourseId);
        $oldTeamName = $this->teamObj->getTeamName($request->projectCourseId);
        
        $expTeamName = explode(' ', $oldTeamName->name);
        $transport=array_slice($expTeamName,0,count($expTeamName)-1);
        $impTeamName = implode(' ', $transport);
        
        $count = $teamCount+1;
        $teamName = $impTeamName. ' '.$count;
        //dd($teamName);
        $data = array(
            'project_course_id' => $request->projectCourseId,
            'name' => $teamName,
            'description' => 'test',
            'created_by' => $university_user->id,
            'updated_by' => $university_user->id,
            'created_at' => $request->updated_at,
            'updated_at' => $request->updated_at,
        );
        $create_team = $this->teamObj->createTeam($data, $user_id);
        return $create_team;
        //return redirect()->back()->with('success', 'Team added successfully.');
    }

    /**
     * Get self enroll team list
     * @param 
     * @return $teamArray
     */
    public function selfEnrollment($pcId = null)
    {
        $array = $team = $teamArray = $projectCourse = $projectCourseId = [];
        $user = Auth::user();

        $user_profile_data = $this->userProfileObj->getUserProfile($user->id);

        $teamList = $this->teamObj->getTeamList($user->university_users->id);
        
        foreach($teamList as $key => $teams){
            $array[$teams->project_course_id][] = $teams->team_id;
        }
        foreach($array as $key => $data){
            $status = $this->teamObj->getStudentPerTeamCount($data, $user->university_users->id);
            if($status){
                $projectCourseId[]=$key;
            }
        }
        if($projectCourseId){
            $projectCourse = $this->projectObj->getProjectByProjectCourseId($projectCourseId);
        }
        if(!isset($pcId)){
            return view('teams.selfEnrollment', compact('user_profile_data', 'projectCourse', 'pcId'));
        }else{
            $teamDetails = $this->teamObj->getEnrollTeamDetails($pcId);
            //dd($teamDetails);
            return view('teams.selfEnrollment', compact('user_profile_data', 'projectCourse', 'pcId', 'teamDetails'));
        }
    }

    /**
     * Student assign themselves to a team
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response 
     */
    public function studentSelfEnrollTeam(Request $request)
    {
        $user = Auth::user();
        $alreadyEnrolled = $this->teamObj->alreadyEnrolled($request->team_id);
        if($alreadyEnrolled == 0){
            $insertData['student_id'] = $user->university_users->id;
            $insertData['team_id'] = $request->team_id;
            $insertData['created_by'] = $user->university_users->id;
            $insertData['created_at'] = $request->updated_at;
            $insertData['updated_at'] = $request->updated_at;
            //dd($insertData);
            $result = $this->teamObj->studentSelfEnrollTeam($insertData);
            if($result){
                return response()->json(array('status' => 'success', 'msg' => 'Team enrolled successfully.'));
            }else{
                return response()->json(array('status' => 'error', 'msg' => 'Unable to process self enrollment.'));
            }
        }else{
            return response()->json(array('status' => 'error', 'msg' => 'You are already enrolled.'));
        }
        //return redirect()->back()->with('success', $result);
    }

    /**
     * Team members from storage
     * @param \Illuminate\Http\Request  $request
     * @return JSON
     */
    public function getTeamMembers(Request $request)
    {
        if ($request->ajax()) {
            $teamMembers = [];
            
            $teamMembers = $this->projectObj->getTeamStudents($request->team_id);
            
            return json_decode(json_encode($teamMembers));
        }
    }
}

<?php

namespace App\Services;

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use App\Services\ProjectService;
use App\Services\UserService;
use App\Services\TimeSpendService;
use App\Services\TeamService;
use App\Models\Team;

use Throwable;
use Carbon\Carbon;

class TimeSpendController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->timeObj = new TimeSpendService();
        $this->projectObj= new ProjectService();
        $this->userObj= new UserService();
        $this->teamObj= new TeamService();
        
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($project_course_id=null, $team_id=null, $isDeleted=false)
    {
        $user=Auth::user();
        $userRole = $user->role_id;
        $userId=$user->university_users->id; 
        if($user->role_id == Config::get('constants.roleTypes.admin'))
        { 
            $teams = [];
            $projects = $this->projectObj->getProjects($userId, $userRole);
            if($project_course_id){
                $teams = $this->teamObj->getTeamsByProjectId($project_course_id);
            } 
            if(isset($team_id)){
                $TimeSpends = $this->timeObj->getSpendTimeList($userId, $team_id, $isDeleted);
            }else{
                if(count($teams) > 0){
                    $TimeSpends = $this->timeObj->getAllSpendTimeList($teams, $isDeleted);
                }else{
                    $TimeSpends = [];
                }
            }
            $TimeCategory = $this->timeObj->getTimeAccountList();
            
            //  dd($TimeSpends);
            $projectCourseIds=[];
            
            $projectCoureses=$projects;   
           //  dd($projectCoureses);
        }elseif($user->role_id == Config::get('constants.roleTypes.faculty') ||$user->role_id == Config::get('constants.roleTypes.ta'))
        {
            $teams = [];
            $projects = $this->projectObj->getProjects($userId, $userRole);
            if($project_course_id){
                $teams = $this->teamObj->getTeamsByProjectId($project_course_id);
            }
            if(isset($team_id)){
                $TimeSpends = $this->timeObj->getSpendTimeList($userId, $team_id. $isDeleted);
            }else{
                if(count($teams) > 0){
                    $TimeSpends = $this->timeObj->getAllSpendTimeList($teams, $isDeleted);
                }else{
                    $TimeSpends = [];
                }
            }
            $TimeCategory = $this->timeObj->getTimeAccountList();
            
            $projectCoureses=$projects;
            
        }else{
            $teams = [];
            $projects = $this->projectObj->getProjects($userId, $userRole);
            if($project_course_id){
                $teams = $this->teamObj->getTeamsByProjectId($project_course_id);
            }
            if(isset($team_id)){
                $TimeSpends = $this->timeObj->getSpendTimeList($userId, $team_id, $isDeleted);
            }else{
                if(count($teams) > 0){
                    $TimeSpends = $this->timeObj->getAllSpendTimeList($teams, $isDeleted);
                }else{
                    $TimeSpends = [];
                }
            }
            //   dd($teams);
            $TimeCategory = $this->timeObj->getTimeAccountList();

            $projectCoureses=$projects;
            
        }
        return view('timespend.index', 
                                    ['timeSpends' => $TimeSpends, 
                                    'timeCategories' => $TimeCategory, 
                                    'projectsCourses' => $projectCoureses,
                                    'team_id' => $team_id,
                                    'project_course_id' =>$project_course_id,
                                    'isDeleted' => $isDeleted, 
                                    'teams'=>$teams]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    { 
            $validator = Validator::make($request->all(), [
                //'timeTeam' => ['required'],
                'spendTitle' => ['required', 'string', 'max:255'],
                //'spendDescription' => ['required', 'string', 'max:255'],
                'spendTime' => ['required'],
            ]);
            if ($validator->fails())
                return redirect()->back()->withErrors($validator);

            $projectData = Team::with('project_course_teams.projects')->where('teams.project_course_id', $request->projectCourseId)->first();
            $projectName = $projectData->project_course_teams->projects->title;
            $result = $this->timeObj->insertTimeSpend($request);

            if ($result==true)
            {
                if($request['timeTeam']){
                    $result= $this->userObj->spendNotification($request['timeTeam'],"time",$projectName);
                }else{
                    $teams = Team::select('id')->where('project_course_id', $request['projectCourseId'])->where('is_deleted', Config::get('constants.is_deleted.false'))->get();
                    foreach($teams as $key => $team){
                        $this->userObj->spendNotification($team->id,"time",$projectName);
                    }
                }
                return redirect()->back()->with('success', 'Data add successfully.');
            }
            if($result=="failed")
            {
                return redirect()->back()->with('error', 'Data insert failed');
            }
            
         return redirect()->back()->with('error', 'Data allready exists!');       
       
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TimeSpend  $TimeSpend
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {           
            $validator = Validator::make($request->all(), [
                'timeTeame' => ['required'],
                'spendTitlee' => ['required', 'string', 'max:255'],
                'spendDescriptione' => ['required', 'string', 'max:255'],
                'spendTimee' => ['required'],
            ]);
            if ($validator->fails())
                return redirect()->back()->withErrors($validator);

            $result = $this->timeObj->updateTimeSpend($request);
            if ($result)
                return redirect()->back()->with('success', 'Data update successfully.');
            return redirect()->back()->with('error', 'Data update failed.');
            // dd($result);
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TimeSpend  $TimeSpend
     * @return \Illuminate\Http\Response
     */
    public function deleteTimeSpends(Request $request)
    {
        $data = [];
        $user = Auth::user();
        $data['is_deleted'] = '1';
        $data['updated_by'] = $user->university_users->id;
        $data['updated_at'] = Carbon::now();
        $result = $this->timeObj->deleteTimeSpend($request->id, $data);
        return response()->json(array('status' => $result['status'], 'msg' => $result['msg']));
            
    }

    /**
     * Get time spend by team id.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchTimeSpend(Request $request)
    {
        if ($request->ajax()) {
           
              $TimeResults =  $this->timeObj->getTimeSpendByTeamId($request->team_id);
            
            return $TimeResults;
        }
    }

}

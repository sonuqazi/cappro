<?php

namespace App\Services;

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use App\Services\ProjectService;
use App\Services\UserService;
use App\Services\MoneySpendService;
use App\Services\TeamService;
use App\Models\Team;

use Throwable;
use Carbon\Carbon;

class MoneySpendController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->moneyObj = new MoneySpendService();
        $this->projectObj= new ProjectService();
        $this->userObj= new UserService();
        $this->teamObj= new TeamService();
     
        
    }
    /**
     * Display a listing of the resource.
     *
     * @param int $project_course_id|$team_id
     * @param boolean
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
            if($team_id){
                $moneySpends = $this->moneyObj->getSpendMoneyList($userId, $team_id, $isDeleted);
            }else{
                if(count($teams) > 0){
                    $moneySpends = $this->moneyObj->getAllSpendMoneyList($teams, $isDeleted);
                }else{
                    $moneySpends = [];
                }
            }
            //dd($moneySpends);
            $moneyCategory = $this->moneyObj->getMoneyCategoryList();
            
            $projectCoureses=$projects;  
           
        }elseif($user->role_id == Config::get('constants.roleTypes.faculty') || $user->role_id == Config::get('constants.roleTypes.ta'))
        {
            $teams = [];
            $projects = $this->projectObj->getProjects($userId, $userRole);
            if($project_course_id){
                $teams = $this->teamObj->getTeamsByProjectId($project_course_id);
            }
            if(isset($team_id)){
                $moneySpends = $this->moneyObj->getSpendMoneyList($userId, $team_id, $isDeleted);
            }else{
                if(count($teams) > 0){
                    $moneySpends = $this->moneyObj->getAllSpendMoneyList($teams, $isDeleted);
                }else{
                    $moneySpends = [];
                }
            }
            $moneyCategory = $this->moneyObj->getMoneyCategoryList();
            
            $projectCoureses=$projects;
            
        }else{
            $teams = [];
            $projects = $this->projectObj->getProjects($userId, $userRole);
            if($project_course_id){
                $teams = $this->teamObj->getTeamsByProjectId($project_course_id);
                // $teams=$this->projectObj->getProjectTeams($project_course_id, '', '', $isDeleted=2);
            } 
            if(isset($team_id)){
                $moneySpends = $this->moneyObj->getSpendMoneyList($userId, $team_id, $isDeleted);
            }else{
                if(count($teams) > 0){
                    $moneySpends = $this->moneyObj->getAllSpendMoneyList($teams, $isDeleted);
                }else{
                    $moneySpends = [];
                }
            }
            //   dd($teams);
            $moneyCategory = $this->moneyObj->getMoneyCategoryList();
            
            $projectCoureses=$projects;
        }
        //Get money account list
        $moneyAccounts = $this->moneyObj->getMoneyAccountList();
            
        return view('moneyspend.index', 
                                    ['moneySpends' => $moneySpends, 
                                    'moneyCategories' => $moneyCategory, 
                                    'projectsCourses' => $projectCoureses,
                                    'project_course_id' =>$project_course_id,
                                    'team_id' => $team_id,
                                    'isDeleted' => $isDeleted,
                                    'teams' => $teams,
                                    'moneyAccounts' => $moneyAccounts]);
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
                //'moneyTeam' => ['required'],
                'spendTitle' => ['required', 'string', 'max:255'],
                //'spendDescription' => ['required', 'string', 'max:255'],
                'spendAmount' => ['required'],
            ]);
            if ($validator->fails())
                return redirect()->back()->withErrors($validator);

            $projectData = Team::with('project_course_teams.projects')->where('teams.project_course_id', $request->projectCourseId)->first();
            $projectName = $projectData->project_course_teams->projects->title;
            
            $result = $this->moneyObj->insertMoneySpend($request);
            if ($result)
            {
                if($request['moneyTeam']){
                    $this->userObj->spendNotification($request['moneyTeam'],"money",$projectName);
                }else{
                    $teams = Team::select('id')->where('project_course_id', $request['projectCourseId'])->where('is_deleted', Config::get('constants.is_deleted.false'))->get();
                    foreach($teams as $key => $team){
                        $this->userObj->spendNotification($team->id,"money",$projectName);
                    }
                }
                return redirect()->back()->with('success', 'Data added successfully.');
            }
            if($result=="failed")
               return redirect()->back()->with('error', 'Data insert failed');
            return redirect()->back()->with('error', 'Data allready exists!');
            
            
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\moneySpend  $moneySpend
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {           
            //dd($request->all());
            $validator = Validator::make($request->all(), [
                'moneyTeame' => ['required'],
                'spendTitlee' => ['required', 'string', 'max:255'],
                'spendDescriptione' => ['required', 'string', 'max:255'],
                'spendAmounte' => ['required'],
            ]);
            if ($validator->fails())
                return redirect()->back()->withErrors($validator);

            $result = $this->moneyObj->updateMoneySpend($request);
            if ($result)
                return redirect()->back()->with('success', 'Data update successfully.');
            return redirect()->back()->with('error', 'Data update failed.');
            // dd($result);
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\moneySpend  $moneySpend
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteMoneySpend(Request $request)
    {
        $data = [];
        $user = Auth::user();
        $data['is_deleted'] = '1';
        $data['updated_by'] = $user->university_users->id;
        $data['updated_at'] = Carbon::now();
        $result = $this->moneyObj->deleteMoneySpend($request->id, $data);
        return response()->json(array('status' => $result['status'], 'msg' => $result['msg']));
            
    }

    /**
     * Retrive money spend data from storage.
     *
     * @param  \App\Models\moneySpend  $moneySpend
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchMoneySpend(Request $request)
    {
        if ($request->ajax()) {
           
              $moneyResults =  $this->moneyObj->getMoneySpendByTeamId($request->team_id);
            
            return $moneyResults;
        }
    }

}

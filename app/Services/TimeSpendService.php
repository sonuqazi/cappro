<?php
namespace App\Services;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use App\Models\TeamStudent;
use App\Models\Team;
use App\Models\TimeSpend;
use App\Models\TimeAccount;
use App\Models\UniversityUser;
use Illuminate\Support\Facades\DB;
class TimeSpendService
{
    /**
     * function for get all team assign to user by id  
     * @param $loginUserId (int)
     */
    public function getTeamByUserId($loginUserId=null){
		
        if(Auth::user()->role_id == Config::get('constants.roleTypes.admin')){
            //   DB::enableQueryLog();
            $team =  TeamStudent::join('teams', 'team_students.team_id', '=', 'teams.id')
                    ->leftjoin('university_users', 'team_students.student_id', '=', 'university_users.id')
                    ->where('teams.is_deleted', false)
                    ->select('teams.id', 'teams.name','teams.project_course_id')->groupBy('teams.id')->get();
		    // dd(DB::getQueryLog());
                   
        }elseif(Auth::user()->role_id == Config::get('constants.roleTypes.faculty')|| Auth::user()->role_id == Config::get('constants.roleTypes.ta'))
        {
            // DB::enableQueryLog();
            $team =  Team::join('project_courses', 'teams.project_course_id', '=', 'project_courses.id')
                    ->join('courses', 'courses.id', '=', 'project_courses.course_id')
                    ->select('teams.id', 'teams.name','teams.project_course_id')
                    ->where('courses.faculty_id', $loginUserId)
                    ->where('teams.is_deleted', false)
                    ->orWhere('courses.ta_id', $loginUserId)->get();
            // dd(DB::getQueryLog()); 
                   
        }else{
            //   DB::enableQueryLog();
             $team =  TeamStudent::join('teams', 'team_students.team_id', '=', 'teams.id')
             ->join('university_users', 'team_students.student_id', '=', 'university_users.id')
             ->select('teams.id', 'teams.name','teams.project_course_id')
             ->where('teams.is_deleted', false)
             ->where('university_users.id', $loginUserId)->groupBy('teams.id')->get();
            //    dd(DB::getQueryLog()); 
        }
        return $team;
		
	}

    /**
     * @description  function for get list of spend Time by teams
     * @param $userId (int)
     */
    public function getSpendTimeList($userId = null, $team_id=null, $isDeleted=false)
    {
         
          $user=Auth::user();
        if($user->role_id == Config::get('constants.roleTypes.faculty') || $user->role_id == Config::get('constants.roleTypes.ta'))
        {
          
            //  DB::enableQueryLog();
            $result= TimeSpend::with('timecategory', 'usercreated_by.university_users', 'userupdated_by.university_users', 'teams')
            ->select('time_spends.id', 'time_spends.title', 'time_spends.time_category_id', 'time_spends.title', 'time_spends.time', 'time_spends.team_id', 'time_spends.description', 'time_spends.created_by', 'time_spends.created_at', 'time_spends.updated_by', 'time_spends.updated_at')
            ->where('time_spends.team_id', $team_id)
            ->where('time_spends.is_deleted', $isDeleted)
            ->orderBy('time_spends.id', 'desc')->get();
            // dd(DB::getQueryLog()); 
            return $result;
            
        }elseif($user->role_id == Config::get('constants.roleTypes.admin'))
        {
                // DB::enableQueryLog();
            $query= TimeSpend::with('timecategory', 'teams')
            ->select('time_spends.id', 'time_spends.title', 'time_spends.time_category_id', 'time_spends.title', 'time_spends.time', 'time_spends.team_id', 'time_spends.description', 'time_spends.created_by', 'time_spends.created_at', 'time_spends.updated_by', 'time_spends.updated_at');
            if($team_id!=null)
            {
                $query->where('time_spends.team_id', $team_id);
            }  
            $query->where('time_spends.is_deleted', $isDeleted);
            //    dd(DB::getQueryLog()); 
            return $query->orderBy('time_spends.id', 'desc')->get();
            
        }else{
            $query= TimeSpend::with('timecategory', 'teams')
            ->select('time_spends.id', 'time_spends.title', 'time_spends.time_category_id', 'time_spends.title', 'time_spends.time', 'time_spends.team_id', 'time_spends.description', 'time_spends.created_by', 'time_spends.created_at', 'time_spends.updated_by', 'time_spends.updated_at');
            if($userId!=null)
            {
                //$query->where('time_spends.created_by', $userId);
            }
            $query->where('time_spends.team_id', $team_id);
            $query->where('time_spends.is_deleted', $isDeleted);
            
            return $query->orderBy('time_spends.id', 'desc')->get();
        }
        // $userId = UniversityUser::where('user_id', '=', Auth::user()->id)->first();
       
    }

    /**
     * @description  function for get all list of spend Time
     * @param $teams (int)
     */
    public function getAllSpendTimeList($teams=null, $isDeleted=false)
    {
        $result = [];
        foreach($teams as $key => $value){
            $team[$key]['teamId'] = $value->team_id;
        } 
        $user=Auth::user();
        if($user->role_id == Config::get('constants.roleTypes.faculty') || $user->role_id == Config::get('constants.roleTypes.ta'))
        {
          
            //  DB::enableQueryLog();
            $result= TimeSpend::with('timecategory', 'usercreated_by.university_users', 'userupdated_by.university_users', 'teams')
            ->select('time_spends.id', 'time_spends.title', 'time_spends.time_category_id', 'time_spends.title', 'time_spends.time', 'time_spends.team_id', 'time_spends.description', 'time_spends.created_by', 'time_spends.created_at', 'time_spends.updated_by', 'time_spends.updated_at')
            ->whereIn('time_spends.team_id', $team)
            ->where('time_spends.is_deleted', $isDeleted)
            ->orderBy('time_spends.id', 'desc')->get();
            // dd(DB::getQueryLog()); 
            return $result;
            
        }elseif($user->role_id == Config::get('constants.roleTypes.admin'))
        {
                // DB::enableQueryLog();
            $query= TimeSpend::with('timecategory', 'teams')
            ->select('time_spends.id', 'time_spends.title', 'time_spends.time_category_id', 'time_spends.title', 'time_spends.time', 'time_spends.team_id', 'time_spends.description', 'time_spends.created_by', 'time_spends.created_at', 'time_spends.updated_by', 'time_spends.updated_at');
            if($teams!=null)
            {
                $query->whereIn('time_spends.team_id', $team);
            }  
            $query->where('time_spends.is_deleted', $isDeleted);
            //    dd(DB::getQueryLog()); 
            return $query->orderBy('time_spends.id', 'desc')->get();
            
        }else{
            $query= TimeSpend::with('timecategory', 'teams')
            ->select('time_spends.id', 'time_spends.title', 'time_spends.time_category_id', 'time_spends.title', 'time_spends.time', 'time_spends.team_id', 'time_spends.description', 'time_spends.created_by', 'time_spends.created_at', 'time_spends.updated_by', 'time_spends.updated_at');
            if($userId!=null)
            {
                //$query->where('time_spends.created_by', $userId);
            }
            $query->whereIn('time_spends.team_id', $team);
            $query->where('time_spends.is_deleted', $isDeleted);
            
            return $query->orderBy('time_spends.id', 'desc')->get();
        }
        // $userId = UniversityUser::where('user_id', '=', Auth::user()->id)->first();
       
    }

    /**
     * @description list of Time category
     * @return Object
     */
    public function getTimeAccountList()
    {
        return TimeAccount::select('id','title')->where('is_active', true)->orderBy('title', 'asc')->get();
    }


    /**
     * @description insert the Time spend 
     * @param $request(object)
     * @param $userId (int)
     */
    public function insertTimeSpend($request)
    {
        $userId = UniversityUser::where('user_id', '=', Auth::user()->id)->first();
        if($request['timeTeam']){
            $condtion = [
                ['title' , $request->input('spendTitle')],
                ['time_category_id' , $request->input('timeCategory')],
                ['team_id', $request['timeTeam']],
                ['created_by', $userId->id]
            ];
            $CountRow=TimeSpend::where($condtion)->count();
            if($CountRow<1)
            {
                $data = array(
                    'title' => $request->input('spendTitle'),
                    'description' => $request->input('spendDescription'),
                    'time_category_id' => $request->input('timeCategory'),
                    'time' => $request->input('spendTime'),
                    'team_id' => $request['timeTeam'],
                    'created_by' => $userId->id,
                    'created_at' => $request['created_at'],
                    'updated_at' => $request['updated_at'],
                    'status' => 0
                );
                $updateDateTime = array(
                    'created_at' => $request['created_at'],
                    'updated_at' => $request['updated_at'] 
                );
                $result=TimeSpend::create($data);
                $insertedId = DB::getPdo()->lastInsertId();
                    
                TimeSpend::whereId($insertedId)->update($updateDateTime);
                //    dd($result);
                if($result)
                {
                    return true;
                }else{
                    return "failed";
                } 

            }else{
                return false; 
            }
        }else{
            $teams = Team::select('id')->where('project_course_id', $request['projectCourseId'])->where('is_deleted', Config::get('constants.is_deleted.false'))->get();
            foreach($teams as $key => $team ){
                $data[] = array(
                    'title' => $request->input('spendTitle'),
                    'description' => $request->input('spendDescription'),
                    'time_category_id' => $request->input('timeCategory'),
                    'time' => $request->input('spendTime'),
                    'team_id' => $team['id'],
                    'created_by' => $userId->id,
                    'created_at' => $request['created_at'],
                    'updated_at' => $request['updated_at'],
                    'status' => 0
                );
            }
            $result=TimeSpend::insert($data);
            if($result)
            {
                return true;
            }
            return "failed";
        }
    }

     /**
     * Update Time Spend
     * @param Object $request
     * @return Object
     */
    public function updateTimeSpend($request)
    {
        $userId = UniversityUser::where('user_id', '=', Auth::user()->id)->first();
        $id = $request->input('timeSpendId');
        // DB::enableQueryLog();
        $data = array(
            'title' => $request->input('spendTitlee'),
            'description' => $request->input('spendDescriptione'),
            'time_category_id' => $request->input('timeCategorye'),
            'time' => $request->input('spendTimee'),
            'team_id' => $request['timeTeame'],
            'updated_by' => $userId->id,
            'updated_at' => $request['updated_at'],
            'status' => 0
        );
        // dd($data);
        $result= TimeSpend::whereId($id)->update($data);
        // dd(DB::getQueryLog()); 
        return $result;
    }

     /**
     * Delete Time Spend
     * @param Int $id
     * @param Array $data
     * @return Object
     */
     public function deleteTimeSpend($id, $data)
    {
        $result = TimeSpend::whereId($id)->update($data);
        if ($result) {
            $message = array('status' => 'success', 'msg' => 'Time Spent deleted successfully.');
        } else {
            $message = array('status' => 'failed', 'msg' => 'Unable to delete time spent.');
        }
        return $message;
    }

     /**
     * Time Spend By Team Id
     * @param Int $teamId
     * @return Object
     */
    public function getTimeSpendByTeamId($teamId=null)
    {
        $user=Auth::user();
        if($user->role_id == Config::get('constants.roleTypes.student'))
        {
            $userId=$user->university_users->id;
            $result= TimeSpend::where('team_id', $teamId)
                            ->where('created_by', $userId)
                            ->where('is_deleted', Config::get('constants.is_deleted.false'))->get();

        }else{
           $result= TimeSpend::where('team_id', $teamId)  
                            ->where('is_deleted', Config::get('constants.is_deleted.false'))->get();
            
        }
       return $result;
    }

    /**
     * Get the sum of time spent hours
     * @return void
     */
    public function getAllHoursSpent($semester_id)
    {
        if(Auth::user()->role_id == '3'){
            $moneySpent = TimeSpend::join('teams', 'teams.id', 'time_spends.team_id')
            ->join('project_courses', 'project_courses.id', 'teams.project_course_id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->where('semester_id', $semester_id)->where('time_spends.is_deleted', '0')
            ->where('courses.faculty_id', Auth::user()->university_users->id)->sum('time');
        }else{
            $moneySpent = TimeSpend::join('teams', 'teams.id', 'time_spends.team_id')
            ->join('project_courses', 'project_courses.id', 'teams.project_course_id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->where('semester_id', $semester_id)->where('time_spends.is_deleted', '0')->sum('time');
        }
        return $moneySpent;
    }
}
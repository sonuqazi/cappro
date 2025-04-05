<?php
namespace App\Services;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use App\Models\TeamStudent;
use App\Models\Team;
use App\Models\MoneySpend;
use App\Models\MoneyCategory;
use App\Models\UniversityUser;
use App\Models\MoneyAccount;
use Illuminate\Support\Facades\DB;
class MoneySpendService
{
    /**
     * function for get all team assign to user by id  
     * @param $loginUserId (int)
     * @return object
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
     * @description  function for get list of spend money by teams
     * @param int $userId
     * @return object
     */
    public function getSpendMoneyList($userId = null, $teams=null, $isDeleted=false)
    {
         
          $user=Auth::user();
        if($user->role_id == Config::get('constants.roleTypes.faculty') || $user->role_id == Config::get('constants.roleTypes.ta'))
        {
            $query= MoneySpend::with('moneycategory', 'usercreated_by.university_users', 'userupdated_by.university_users', 'teams')
             ->select('money_spends.id', 'money_spends.title', 'money_spends.money_account_id', 'money_spends.money_category_id', 'money_spends.title', 'money_spends.amount', 'money_spends.team_id', 'money_spends.description', 'money_spends.created_by', 'money_spends.created_at', 'money_spends.updated_by', 'money_spends.updated_at');
             if($teams!=null)
             {
                 $query->where('money_spends.team_id', $teams);
             }
             $query->where('money_spends.is_deleted', $isDeleted);
             
            $result= $query->orderBy('money_spends.id', 'DESC')->get();
            return $result;
            
        }elseif($user->role_id == Config::get('constants.roleTypes.admin'))
        {
            //   DB::enableQueryLog();
            $query= MoneySpend::with('moneycategory', 'usercreated_by.university_users', 'userupdated_by.university_users', 'teams')
             ->select('money_spends.id', 'money_spends.title', 'money_spends.money_account_id', 'money_spends.money_category_id', 'money_spends.title', 'money_spends.amount', 'money_spends.team_id', 'money_spends.description', 'money_spends.created_by', 'money_spends.created_at', 'money_spends.updated_by', 'money_spends.updated_at');
            if($teams!=null)
            {
                $query->where('money_spends.team_id', $teams);
            }
            $query->where('money_spends.is_deleted', $isDeleted);
            
            $result= $query->orderBy('money_spends.id', 'DESC')->get();
            //   dd(DB::getQueryLog()); 
            return $result;
            
        }else{
            // DB::enableQueryLog();
            $query= MoneySpend::with('moneycategory', 'usercreated_by.university_users', 'userupdated_by.university_users', 'teams')
            ->select('money_spends.id', 'money_spends.title', 'money_spends.money_account_id', 'money_spends.money_category_id', 'money_spends.title', 'money_spends.amount', 'money_spends.team_id', 'money_spends.description', 'money_spends.created_by', 'money_spends.created_at', 'money_spends.updated_by', 'money_spends.updated_at') 
            ->where('money_spends.team_id', $teams)
            //->where('money_spends.created_by', $userId)
            ->where('money_spends.is_deleted', $isDeleted)
            ->orderBy('money_spends.id', 'DESC')->get();
            // dd(DB::getQueryLog()); 
            return $query;
        }
        // $userId = UniversityUser::where('user_id', '=', Auth::user()->id)->first();
       
    }

    /**
     * @description  function for get all list of spend money
     * @param int $teams
     * @return object
     */
    public function getAllSpendMoneyList($teams = null, $isDeleted=false)
    {
        $result = [];
        foreach($teams as $key => $value){
            $team[$key]['teamId'] = $value->team_id;
        }
        $user=Auth::user();
        if($user->role_id == Config::get('constants.roleTypes.faculty') || $user->role_id == Config::get('constants.roleTypes.ta'))
        {
            $query= MoneySpend::with('moneycategory', 'usercreated_by.university_users', 'userupdated_by.university_users', 'teams')
             ->select('money_spends.id', 'money_spends.title', 'money_spends.money_account_id', 'money_spends.money_category_id', 'money_spends.title', 'money_spends.amount', 'money_spends.team_id', 'money_spends.description', 'money_spends.created_by', 'money_spends.created_at', 'money_spends.updated_by', 'money_spends.updated_at');
             if($teams!=null)
             {
                 $query->whereIn('money_spends.team_id', $team);
             }
             $query->where('money_spends.is_deleted', $isDeleted);
             
            $result= $query->orderBy('money_spends.id', 'DESC')->get();
            return $result;
            
        }elseif($user->role_id == Config::get('constants.roleTypes.admin'))
        {
            //   DB::enableQueryLog();
            $query= MoneySpend::with('moneycategory', 'usercreated_by.university_users', 'userupdated_by.university_users', 'teams')
             ->select('money_spends.id', 'money_spends.title', 'money_spends.money_account_id', 'money_spends.money_category_id', 'money_spends.title', 'money_spends.amount', 'money_spends.team_id', 'money_spends.description', 'money_spends.created_by', 'money_spends.created_at', 'money_spends.updated_by', 'money_spends.updated_at');
            if($teams!=null)
            {
                $query->whereIn('money_spends.team_id', $team);
            }
            $query->where('money_spends.is_deleted', $isDeleted);
            
            $result= $query->orderBy('money_spends.id', 'DESC')->get();
            //   dd(DB::getQueryLog()); 
            return $result;
            
        }else{
            // DB::enableQueryLog();
            $query= MoneySpend::with('moneycategory', 'usercreated_by.university_users', 'userupdated_by.university_users', 'teams')
            ->select('money_spends.id', 'money_spends.title', 'money_spends.money_account_id', 'money_spends.money_category_id', 'money_spends.title', 'money_spends.amount', 'money_spends.team_id', 'money_spends.description', 'money_spends.created_by', 'money_spends.created_at', 'money_spends.updated_by', 'money_spends.updated_at') 
            ->where('money_spends.team_id', $teams)
            //->where('money_spends.created_by', $userId)
            ->where('money_spends.is_deleted', $isDeleted)
            ->orderBy('money_spends.id', 'DESC')->get();
            // dd(DB::getQueryLog()); 
            return $query;
        }
        // $userId = UniversityUser::where('user_id', '=', Auth::user()->id)->first();
       
    }

    /**
     * @description list of money category
     * @return object
     */
    public function getMoneyCategoryList()
    {
        return MoneyCategory::select('id','title')->where('is_active', true)->orderBy('title', 'asc')->get();
    }

    /**
     * @description list of money account
     * @return object
     */
    public function getMoneyAccountList()
    {
        return MoneyAccount::select('id','title')->where('is_active', true)->where('is_deleted', 0)->orderBy('title', 'asc')->get();
    }


    /**
     * @description insert the money spend 
     * @param object $request,
     * @return boolean
     */
    public function insertMoneySpend($request)
    {
        $userId = UniversityUser::where('user_id', '=', Auth::user()->id)->first();
        if($request['moneyTeam']){
            $condtion = [
                ['title' , $request->input('spendTitle')],
                ['money_account_id' , $request->input('moneyAccount')],
                ['money_category_id' , $request->input('moneyCategory')],
                ['team_id', $request['moneyTeam']],
                ['created_by', $userId->id]
            ];
            $CountRow=MoneySpend::where($condtion)->count();
            if($CountRow<1)
            {   
                $data = array(
                    'title' => $request->input('spendTitle'),
                    'description' => $request->input('spendDescription'),
                    'money_account_id' => $request->input('moneyAccount'),
                    'money_category_id' => $request->input('moneyCategory'),
                    'amount' => $request->input('spendAmount'),
                    'team_id' => $request['moneyTeam'],
                    'created_by' => $userId->id,
                    'updated_by' => $userId->id,
                    'created_at' => $request['created_at'],
                    'updated_at' => $request['updated_at'],
                    'status' => 0
                );
                $updateDateTime = array(
                    'created_at' => $request['created_at'],
                    'updated_at' => $request['updated_at'] 
                );
                //dd($updateDateTime);
                $result=MoneySpend::create($data);
                $insertedId = DB::getPdo()->lastInsertId();
                
                MoneySpend::whereId($insertedId)->update($updateDateTime);
                if($result)
                {
                    return true;
                }
                return "failed";
            }else{
                return false;
            }
        }else{
            $teams = Team::select('id')->where('project_course_id', $request['projectCourseId'])->where('is_deleted', Config::get('constants.is_deleted.false'))->get();
            foreach($teams as $key => $team ){
                $data[] = array(
                    'title' => $request->input('spendTitle'),
                    'description' => $request->input('spendDescription'),
                    'money_account_id' => $request->input('moneyAccount'),
                    'money_category_id' => $request->input('moneyCategory'),
                    'amount' => $request->input('spendAmount'),
                    'team_id' => $team['id'],
                    'created_by' => $userId->id,
                    'updated_by' => $userId->id,
                    'created_at' => $request['created_at'],
                    'updated_at' => $request['updated_at'],
                    'status' => 0
                );
            }
            $result=MoneySpend::insert($data);
            if($result)
            {
                return true;
            }
            return "failed";
        }
    }
    /**
     * @description Update Money Spend
     * @param object $request
     * @return boolean
     */
    public function updateMoneySpend($request)
    {
        $userId = UniversityUser::where('user_id', '=', Auth::user()->id)->first();
        $id = $request->input('moneySpendId');
        $data = array(
            'title' => $request->input('spendTitlee'),
            'description' => $request->input('spendDescriptione'),
            'money_account_id' => $request->input('moneyAccount'),
            'money_category_id' => $request->input('moneyCategorye'),
            'amount' => $request->input('spendAmounte'),
            'team_id' => $request['moneyTeame'],
            'updated_by' => $userId->id,
            'updated_at' => $request['updated_at'],
            'status' => 0
        );
        return MoneySpend::whereId($id)->update($data);
    }

    /**
     * @description delete Money Spend
     * @param array $data, int $id
     * @return Array
     */
     public function deleteMoneySpend($id, $data)
    {
        $result = MoneySpend::whereId($id)->update($data);
        if ($result) {
            $message = array('status' => 'success', 'msg' => 'Money spent deleted successfully.');
        } else {
            $message = array('status' => 'failed', 'msg' => 'Unable to delete money spent.');
        }
        return $message;
    }

    /**
     * @description  Money Spend By Team
     * @param int $teamId
     * @return object
     */
    public function getMoneySpendByTeamId($teamId=null)
    {
        $user=Auth::user();
        if($user->role_id == Config::get('constants.roleTypes.student'))
        {
            $userId=$user->university_users->id;
            $result= MoneySpend::where('team_id', $teamId)
                            ->where('created_by', $userId)
                            ->where('is_deleted', Config::get('constants.is_deleted.false'))->get();

        }else{
           $result= MoneySpend::where('team_id', $teamId)  
                            ->where('is_deleted', Config::get('constants.is_deleted.false'))->get();
            
        }
       return $result;
    }

    /**
     * Get the sum of money spent amount
     * @return void
     */
    public function getAllMoneySpent($semester_id)
    {
        if(Auth::user()->role_id == '3'){
            $moneySpent = MoneySpend::join('teams', 'teams.id', 'money_spends.team_id')
            ->join('project_courses', 'project_courses.id', 'teams.project_course_id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->where('semester_id', $semester_id)->where('money_spends.is_deleted', '0')
            ->where('courses.faculty_id', Auth::user()->university_users->id)->sum('amount');
        }else{
            $moneySpent = MoneySpend::join('teams', 'teams.id', 'money_spends.team_id')
            ->join('project_courses', 'project_courses.id', 'teams.project_course_id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->where('semester_id', $semester_id)->where('money_spends.is_deleted', '0')->sum('amount');
        }
        return $moneySpent;
    }
}
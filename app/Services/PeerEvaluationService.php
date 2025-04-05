<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\PeerEvaluationStart;
use App\Models\Team;
use App\Models\TeamStudent;
use App\Models\PeerEvaluationRating;
use App\Models\PeerEvaluationRatingStar;
use App\Models\ProjectCourse;
use App\Models\PeerEvaluation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class PeerEvaluationService
{
    /**
     * @description All Peer Evalution
     * @param int $user_id
     * @return boolean
     */
    public function getAllPeerEval($user_id)
    {
        try {
            $user_profile_data = User::where('id',$user_id)->with('user_profiles')->first();
        return $user_profile_data;
        }  catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Save or update template
     * @param object $request, int $user_id
     * @return string
     */
    public function update_template($request, $user_id)
    {
        //dd($request);
        $university_user =  DB::table('university_users')->select('id')
            ->Where('user_id', $user_id)->first();

        $template = array(
            'name' => $request['name'],
            'description' => $request['description'],
        );
        // dd($template);
        if (!empty($request['id'])) {
            // dd('here');
            $template['updated_by'] = $university_user->id;
            $template['updated_at'] = $request['updated_at'];
            DB::table('peer_evaluations')
                ->where('id', $request['id'])
                ->update($template);

            $eval_id = $request['id'];
            $msg = 'Peer evaluation template updated successfully';
        } else {
            $template['created_by'] = $university_user->id;
            $template['created_at'] = $request['created_at'];
            $template['updated_at'] = $request['updated_at'];
            DB::table('peer_evaluations')->insert($template);

            $eval_id = DB::getPdo()->lastInsertId();
            $msg = 'Peer evaluation template added successfully';
        }

        if (!empty($request['rating'])) {
            $add_milestone_name_arr = $request['rating'];
            $add_milestone_description_arr = $request['rating_description'];
            $add_milestone_id_arr = isset($request['peer_evaluation_id']) ? $request['peer_evaluation_id'] : '';

            // $count_new_milestones = count($add_milestone_name_arr);
            $order_counter = 1;
            foreach ($add_milestone_name_arr as $count_milestone => $single_milestone_name) {
                if (empty($add_milestone_id_arr[$count_milestone])) {
                    $insert_milestone['rating'] = $single_milestone_name;
                    $insert_milestone['description'] = $add_milestone_description_arr[$count_milestone];
                    $insert_milestone['peer_evaluation_id'] = $eval_id;
                    // $insert_milestone['created_by'] = $university_user->id;
                    $insert_milestone['created_at'] = $request['created_at'];
                    $insert_milestone['order_counter'] = $order_counter;

                    DB::table('peer_evaluation_ratings')->insert($insert_milestone);
                } else {
                    $update_milestone['rating'] = $single_milestone_name;
                    $update_milestone['description'] = $add_milestone_description_arr[$count_milestone];
                    //     $update_milestone['updated_by'] = $university_user->id;
                    $update_milestone['updated_at'] = $request['updated_at'];
                    $update_milestone['order_counter'] = $order_counter;

                    DB::table('peer_evaluation_ratings')
                        ->where('id', $add_milestone_id_arr[$count_milestone])
                        ->update($update_milestone);

                    if (isset($request['milestone_deleted'])) {
                        $del_arr = explode(",", $request['milestone_deleted']);
                        foreach ($del_arr as $del_id) {
                            DB::table('peer_evaluation_ratings')
                                ->where('id', $del_id)
                                ->update(['is_deleted' => 1]);
                        }
                    }
                }

                $order_counter++;
            }
        }
        return $msg;
    }

    /**
     * @description Function get count of filtered templates
     * @param object $request
     * @return Array
     */
    public function search_all_template_counts($request)
    {
        $keywords = $request->search['value'];
        $queries = DB::table('peer_evaluations')->select('peer_evaluations.id')->where('peer_evaluations.is_active', '1');
        //->where('peer_evaluations.is_deleted', '0');

        $keywords = trim($keywords);
        if (!empty($keywords)) {
            $search_words =  explode(' ', $keywords);
            $firstword = $search_words[0];
            $lastword = @$search_words[1];

            if (empty($lastword)) {
                $queries->Where(function ($query) use ($firstword) {
                    $query->where('peer_evaluations.name', 'like', '%' . $firstword . '%')
                        ->orWhere('peer_evaluations.description', 'like', '%' . $firstword . '%');
                });
            } else {
                $queries->Where(function ($query) use ($firstword, $lastword) {
                    $query->where('peer_evaluations.name', 'like', '%' . $firstword . '%')
                        ->Where('peer_evaluations.name', 'like', '%' . $lastword . '%')
                        ->orWhere('peer_evaluations.description', 'like', '%' . $firstword . ' ' . $lastword . '%');
                });
            }
        }


        return $queries->count();
    }

    /**
     * List all templates.
     * @param object $request
     * @return $request
     */

    public function alltemplates($request)
    {
        $keywords = trim($request->search['value']);
        $start = $request->start;
        $limit = $request->length;
        $order_index = $request->order[0]['column'];
        $order_by = $request->columns[$order_index]['data'];
        $sort_by = $request->order[0]['dir'];
        $queries =  PeerEvaluation::with('usercreated_by.university_users', 'userupdated_by.university_users')->select('peer_evaluations.id', 'peer_evaluations.name', 'peer_evaluations.description', 'peer_evaluations.is_active', 'peer_evaluations.is_deleted', 'peer_evaluations.is_assigned', 'peer_evaluations.created_by', 'peer_evaluations.created_at', 'peer_evaluations.updated_by', 'peer_evaluations.updated_at', DB::raw('COUNT(peer_evaluation_ratings.id) as no_rating'))
           //->where('peer_evaluations.is_deleted', '0')
           ->where(function($query){
                //$query->whereNull('peer_evaluation_ratings.id');
                $query->Where('peer_evaluation_ratings.is_deleted', '0');
                
            })
            ->where('peer_evaluations.is_active', '1')
            ->leftJoin('peer_evaluation_ratings', 'peer_evaluation_ratings.peer_evaluation_id', '=', 'peer_evaluations.id');
        
        $keywords = trim($keywords);
        if (!empty($keywords)) {
            $search_words =  explode(' ', $keywords);
            $firstword = $search_words[0];
            $lastword = @$search_words[1];

            if (empty($lastword)) {
                $queries->Where(function ($query) use ($firstword) {
                    $query->where('peer_evaluations.name', 'like', '%' . $firstword . '%')
                        ->orWhere('peer_evaluations.description', 'like', '%' . $firstword . '%');
                });
            } else {
                $queries->Where(function ($query) use ($firstword, $lastword) {
                    $query->where('peer_evaluations.name', 'like', '%' . $firstword . '%')
                        ->Where('peer_evaluations.name', 'like', '%' . $lastword . '%')
                        ->orWhere('peer_evaluations.description', 'like', '%' . $firstword . ' ' . $lastword . '%');
                });
            }
        }
        return $queries = $queries->groupBy('peer_evaluations.id')->orderBy($order_by, $sort_by)
            ->limit($limit, $start)
            ->get();
    }

    /**
     * fetch the questions saved in a template
     * @param object $request 
     * @return
     */
    public function get_all_ratings_with_template($request)
    {
        return DB::table('peer_evaluation_ratings')->select('id', 'rating','description')
            ->Where('peer_evaluation_id', $request->peer_id)
            ->Where('is_deleted', '0')
            ->orderBy('order_counter', 'asc')->get();
    }

    /** 
     * inactive template
     * @param int $id
     * @return boolean
     */
    public  function deleteTemplate($id, $dateTime)
    {
        DB::table('peer_evaluations')->where('id', $id)->update(['is_active' => 0, 'updated_by' => Auth::user()->university_users->id, 'updated_at' => $dateTime]);
        return true;
    }

    /**
     * Save or update time
     * @param object $request
     * @return
     */
    public function update_time($request, $user_id)
    {
       // dd($request);
        $university_user =  DB::table('university_users')->select('id')
            ->Where('user_id', $user_id)->first();

        $account = array(
            'title' => $request['title'],
            'description' => $request['description'],
        );
        // dd($template);
        if (!empty($request['id'])) {
            // dd('here');
            $account['updated_by'] = $university_user->id;
            $account['updated_at'] = date('Y-m-d H:i:s');
            DB::table('time_accounts')
                ->where('id', $request['id'])
                ->update($account);

            $acc_id = $request['id'];
            $msg = 'Time account updated successfully';
        } else {
            $account['created_by'] = $university_user->id;
            $account['created_at'] = date('Y-m-d H:i:s');
            DB::table('time_accounts')->insert($account);

            $acc_id = DB::getPdo()->lastInsertId();
            $msg = 'Time account added successfully';
        }

        if (!empty($request['category_name'])) {
            $add_category_name_arr = $request['category_name'];
            $add_cateory_description_arr = $request['category_description'];
            $add_category_id_arr = isset($request['time_account_id']) ? $request['time_account_id'] : '';

           // $count_new_milestones = count($add_milestone_name_arr);
            $order_counter = 1;
            foreach ($add_category_name_arr as $count_category => $single_category_name) {
                if (empty($add_category_id_arr[$count_category])) {
                    $insert_category['name'] = $single_category_name;
                    $insert_category['description'] = $add_cateory_description_arr[$count_category];
                    $insert_category['time_account_id'] = $acc_id;
                    $insert_category['created_at'] = date('Y-m-d H:i:s');
                    $insert_category['order_counter'] = $order_counter;

                    DB::table('time_categories')->insert($insert_category);
                } else {
                    $update_category['name'] = $single_category_name;
                    $update_category['description'] = $add_cateory_description_arr[$count_category];
                    $update_category['updated_at'] = date('Y-m-d H:i:s');
                    $update_category['order_counter'] = $order_counter;

                    DB::table('time_categories')
                        ->where('id', $add_category_id_arr[$count_category])
                        ->update($update_category);

                    if (isset($request['milestone_deleted'])) {
                        $del_arr = explode(",", $request['milestone_deleted']);
                        foreach ($del_arr as $del_id) {
                            DB::table('time_categories')
                                ->where('id', $del_id)
                                ->update(['is_deleted' => 1]);
                        }
                    }
                }

                $order_counter++;
            }
       }
        return $msg;
    }

    /**
     * @description Function get count of filtered time accounts
     * @param object $request
     * @return Array
     */
    public function search_all_time_account_counts($request)
    {
        $keywords = $request->search['value'];
        $queries = DB::table('time_accounts')->select('time_accounts.id')
        ->where('time_accounts.is_deleted', '0');
        // ->where('users.role_id',  '=', '2')
        // ->join('roles', 'roles.id', '=', 'users.role_id');

        $keywords = trim($keywords);
        if (!empty($keywords)) {
            $search_words =  explode(' ', $keywords);
            $firstword = $search_words[0];
            $lastword = @$search_words[1];

            if (empty($lastword)) {
                $queries->Where(function ($query) use ($firstword) {
                    $query->where('time_accounts.title', 'like', '%' . $firstword . '%')
                        ->orWhere('time_accounts.description', 'like', '%' . $firstword . '%');
                    // ->orWhere('users.email', 'like', '%' . $firstword . '%')
                    //  ->orWhere('users.user_name', 'like', '%' . $firstword . '%')
                    //   ->orWhere('roles.name', 'like', '%' . $firstword . '%');
                });
            } else {
                $queries->Where(function ($query) use ($firstword, $lastword) {
                    $query->where('time_accounts.title', 'like', '%' . $firstword . '%')
                        ->Where('time_accounts.title', 'like', '%' . $lastword . '%')
                        ->orWhere('time_accounts.description', 'like', '%' . $firstword . ' ' . $lastword . '%');
                });
            }
        }


        return $queries->count();
    }

    /**
     * List all time accounts.
     * @param object $request
     * @return object
     */

    public function allTimeAccounts($request)
    {
        $keywords = trim($request->search['value']);
        $start = $request->start;
        $limit = $request->length;
        $order_index = $request->order[0]['column'];
        $order_by = $request->columns[$order_index]['data'];
        $sort_by = $request->order[0]['dir'];
        $queries =  DB::table('time_accounts')->select('time_accounts.id', 'time_accounts.title', 'time_accounts.description', 'time_accounts.is_active', 'time_accounts.is_deleted', 'time_accounts.is_assigned', DB::raw('COUNT(time_categories.id) as no_cat'))
           ->where('time_accounts.is_deleted', '0')
           ->where(function($query){
                $query->whereNull('time_categories.id');
                $query->orWhere('time_categories.is_deleted', '0');
                
            })
        //    ->where('evaluation_questions.is_deleted', '0')
            //->where('users.role_id',  '=', '2')
            ->leftJoin('time_categories', 'time_categories.time_account_id', '=', 'time_accounts.id');
        // ->where('users.role_id',  '=', '2')

        $keywords = trim($keywords);
        if (!empty($keywords)) {
            $search_words =  explode(' ', $keywords);
            $firstword = $search_words[0];
            $lastword = @$search_words[1];

            if (empty($lastword)) {
                $queries->Where(function ($query) use ($firstword) {
                    $query->where('time_accounts.title', 'like', '%' . $firstword . '%')
                        ->orWhere('time_accounts.description', 'like', '%' . $firstword . '%');
                    // ->orWhere('users.email', 'like', '%' . $firstword . '%')
                    //  ->orWhere('users.user_name', 'like', '%' . $firstword . '%')
                    //   ->orWhere('roles.name', 'like', '%' . $firstword . '%');
                });
            } else {
                $queries->Where(function ($query) use ($firstword, $lastword) {
                    $query->where('time_accounts.title', 'like', '%' . $firstword . '%')
                        ->Where('time_accounts.title', 'like', '%' . $lastword . '%')
                        ->orWhere('time_accounts.description', 'like', '%' . $firstword . ' ' . $lastword . '%');
                });
            }
        }
        // if (!empty($request->course_id)) {
        //     $queries->Where('users.role_id', '4');
        // }
        // return $queries = $queries->Where('users.role_id', '3')->orderBy($order_by, $sort_by)
        return $queries = $queries->groupBy('time_accounts.id')->orderBy($order_by, $sort_by)
            ->limit($limit, $start)
            ->get()->toArray();
    }

     /**
     * fetch the milestones saved ina pm plans
     * @param object $request
     * @return object
     */
    public function get_all_categories_with_time($request)
    {
        return DB::table('time_categories')->select('id', 'name' , 'description')
            ->Where('time_account_id', $request->time_id)
            ->Where('is_deleted', '0')
            ->orderBy('order_counter', 'asc')->get();
    }

    /** 
     * Delete Time Category
     * @param int $id
     * @return boolean
     */
    public  function deleteTime($id)
    {
        DB::table('time_accounts')->where('id', $id)->update(['is_deleted' => 1]);
        return true;
    }

    /** 
     * Create a peer evaluation ratting
     * @param object $request
     * @return boolean
     */
    public function startEvaluationRatting($request)
    {
        try {
            $teamArray=$this->getTeamForEvaluation($request['project_course_id']);
            
            if(count($teamArray) == 0){
                return false;
            }else{
                foreach($teamArray as $team){
                    $data[] = array(
                        'peer_evaluation_id' => $request['peer_evaluation_id'],
                        'start_date' => $request['start_date'],
                        'end_date' => $request['end_date'],
                        'project_course_id' => $team->project_course_id,
                        'team_id' => $team->team_id,
                        'created_by' => $request['created_by'],
                        'updated_by' => $request['created_by'],
                        'created_at' => $request['updated_at'],
                        'updated_at' => $request['updated_at']
                    );
                }
                //dd($data);
                $result =   PeerEvaluationStart::insert($data);
                return $result;
            }
            
        } catch (Throwable $e) {
            return false;
        }
    }

    /** 
     * Get teams for evaluation
     * @param int $projectId
     * @return object|boolean $teams
     */
    public function getTeamForEvaluation($projectCourseId = null)
    {
        try {
            $teams = Team::join('project_courses', 'project_courses.id', '=', 'teams.project_course_id')
                ->select('teams.id as team_id', 'project_courses.id as project_course_id')
                ->where('teams.is_deleted', 0)
                ->where('project_courses.id', $projectCourseId)->get();
            return $teams;
        } catch (Throwable $e) {

            return false;
        }
    }
    /**
     * @description uSers Team
     * @param int $universityUseriD, boolean $status
     * @return boolean
     */
    public function userTeam($universityUserId = null, $status)
    {
        $studentTeams = TeamStudent::where('team_students.student_id', $universityUserId->id)->get();
        
        $teamData= $temp = [];
        foreach($studentTeams as $key => $teams){
            $result = $this->checkTeamExist($teams->team_id);
            
            if($result){
                
                $temp = $this->getTeamData($teams->team_id, $status);
                
                if(isset($temp[0]['peer_evaluation_id'])){
                    $teamData[$key] = $temp;
                    $teamData[$key]['criteria'] = $this->getEvaluationCriteria($temp[0]['peer_evaluation_id']);
    
                    $teamData[$key]['students'] = $this->getTeamStudents($teams->team_id, $universityUserId->id, Auth::user()->role_id);
    
                    $teamData[$key]['rated'] = $this->checkIfRated($temp[0]['project_course_id'], $teams->team_id, $universityUserId->id);
                }
            }
        }
        //dd($teamData);
        return $teamData;
    }

    /**
     * @description uSers Team
     * @param int $universityUseriD, boolean $status
     * @return boolean
     */
    public function getUserTeam($universityUserId = null, $status, $semId=null)
    {
        $studentTeams = TeamStudent::where('team_students.student_id', $universityUserId->id)->where('team_students.is_deleted', 0)->get();
        $today = strtotime(date('Y-m-d'));
        $resultArray = $teamData = $temp = $orderBy = $oldOrderBy = $newOrderBy = $resultD = $teamDatas = $teamDatass = [];
        foreach($studentTeams as $key => $teams){
            $result = $this->checkTeamExist($teams->team_id);
            
            if($result){
                
                $temp = $this->getUserTeamData($teams->team_id, $status, $semId);
                
                if(isset($temp[0]['peer_evaluation_id'])){
                    $orderBy[$key] = strtotime($temp[0]['end_date']);
                    $teamData[$key] = $temp;
                    $teamData[$key]['orderBy'] = strtotime($temp[0]['end_date']);
                    $teamData[$key]['criteria'] = $this->getEvaluationCriteria($temp[0]['peer_evaluation_id']);
    
                    $teamData[$key]['students'] = $this->getTeamStudents($teams->team_id, $universityUserId->id, Auth::user()->role_id);
    
                    $teamData[$key]['rated'] = $this->checkIfRated($temp[0]['project_course_id'], $teams->team_id, $universityUserId->id);
                }
            }
        }
        arsort($orderBy);
        
        foreach($teamData as $key => $data){
            
            if($data['orderBy'] < $today){
                $oldOrderBy[$key] = $data['orderBy'];
                $resultD['oldData'][$key] = $data;
                $resultD['oldData'][$key]['criteria'] = $data['criteria'];
                $resultD['oldData'][$key]['students'] = $data['students'];
                $resultD['oldData'][$key]['rated'] = $data['rated'];
            }else{
                $newOrderBy[$key] = $data['orderBy'];
                $resultD['newData'][$key] = $data;
                $resultD['newData'][$key]['criteria'] = $data['criteria'];
                $resultD['newData'][$key]['students'] = $data['students'];
                $resultD['newData'][$key]['rated'] = $data['rated'];
            }
        }
        asort($newOrderBy);
        arsort($oldOrderBy);
        
        foreach($newOrderBy as $key1 => $data){
            foreach($resultD['newData'] as $key2 => $value){
                
                if($data == $value['orderBy'] ){
                    $teamDatas[$key2]['0'] = $value[0];
                    $teamDatas[$key2]['criteria'] = $value['criteria'];
    
                    $teamDatas[$key2]['students'] = $value['students'];
    
                    $teamDatas[$key2]['rated'] = $value['rated'];
                    
                }
            }
        }
        foreach($oldOrderBy as $key1 => $data){
            foreach($resultD['oldData'] as $key2 => $value){
                if($data == $value['orderBy']){
                    $teamDatass[$key1]['0'] = $value[0];
                    $teamDatass[$key1]['criteria'] = $value['criteria'];
    
                    $teamDatass[$key1]['students'] = $value['students'];
    
                    $teamDatass[$key1]['rated'] = $value['rated'];
                }
            }
        }
        
        $resultArray['new'] = $teamDatas;
        $resultArray['old'] = $teamDatass;
        
        return $resultArray;
    }

    /**
     * @description Check Team Exit by Team ID
     * @param int $teamId
     * @return object
     */
    public function checkTeamExist($teamId = null)
    {
        $isTeamExist = PeerEvaluationStart::where('peer_evaluation_starts.team_id', $teamId)->exists();
        return $isTeamExist;
    }

    public function getTeamData($teamId = null, $status=null)
    {
        $teamData = PeerEvaluationStart::select('peer_evaluation_starts.*')->with(['peer_evaluations','teams', 'project_courses.projects', 'project_courses'=>function($q){
                $q->with(['courses'=>function($q){
                    $q->with('semesters')->get();
                },])->get();
            }])
            ->join('project_courses', 'project_courses.id', '=', 'peer_evaluation_starts.project_course_id')
            ->join('projects', 'projects.id', '=', 'project_courses.project_id')
            ->where('peer_evaluation_starts.team_id', $teamId)
            ->where('projects.status', $status)->get();
        return $teamData;
    }

    public function getUserTeamData($teamId = null, $status=null, $semId=null)
    {
        $query = PeerEvaluationStart::select('peer_evaluation_starts.*')->with(['peer_evaluations','teams', 'project_courses.projects', 'project_courses'=>function($q){
                $q->with(['courses'=>function($q){
                    $q->with('semesters')->get();
                },])->get();
            }])
            ->join('project_courses', 'project_courses.id', '=', 'peer_evaluation_starts.project_course_id')
            ->join('projects', 'projects.id', '=', 'project_courses.project_id')
            ->join('courses', 'courses.id', '=', 'project_courses.course_id')
            ->where('peer_evaluation_starts.team_id', $teamId)
            ->where('projects.status', '>', $status);
            if($semId){
                $query->where('courses.semester_id', $semId);
            }
            
            //$query->groupBy('peer_evaluation_starts.project_course_id');
            $teamData = $query->get();
        return $teamData;
    }

    public function getTeamDataForEval($teamId = null, $status=null)
    {
        $teamData = PeerEvaluationStart::select('peer_evaluation_starts.*')->with(['peer_evaluations','teams', 'project_courses.projects', 'project_courses'=>function($q){
                $q->with(['courses'=>function($q){
                    $q->with('semesters')->get();
                },])->get();
            }])
            ->join('project_courses', 'project_courses.id', '=', 'peer_evaluation_starts.project_course_id')
            ->join('projects', 'projects.id', '=', 'project_courses.project_id')
            ->where('peer_evaluation_starts.team_id', $teamId)
            ->where('projects.status', '>', $status)->get();
        return $teamData;
    }

    /**
     * @description Evaluation criteria
     * @param int $evalId
     * @return object
     */
    public function getEvaluationCriteria($evalId = null)
    {
        $result = PeerEvaluationRating::where('peer_evaluation_ratings.peer_evaluation_id', $evalId)
        ->where('peer_evaluation_ratings.is_deleted', 0)->get();
        return $result;
    }


    /**
     * @description Team Studebts
     * @param int $teamId, $userId, $roleId
     * @return object
     */
    public function getTeamStudents($teamId = null, $userId = null, $roleId = null)
    {
        if($roleId != 4){
            $result = TeamStudent::join('university_users', 'university_users.id', '=', 'team_students.student_id')
            ->join('users', 'users.id', '=', 'university_users.user_id')
            ->select('users.first_name as first_name', 'users.last_name as last_name', 'users.profile_image as profile_image', 'university_users.id as rate_by')
            ->where('team_students.team_id', $teamId)
            ->where('team_students.is_deleted', 0)->orderBy('student_id', 'asc')->get();
            return $result;
        }else{
            $result = TeamStudent::join('university_users', 'university_users.id', '=', 'team_students.student_id')
            ->join('users', 'users.id', '=', 'university_users.user_id')
            ->select('users.first_name as first_name', 'users.last_name as last_name', 'users.profile_image as profile_image', 'university_users.id as rate_to')
            ->where('team_students.team_id', $teamId)
            ->where('team_students.is_deleted', 0)
            ->where('team_students.student_id', '!=', $userId)->orderBy('student_id', 'asc')->get();
            return $result;
        }
    }

    /**
     * @description Team Studebts
     * @param int $projectCourseId, $teamId, $createdBy
     * @return object
     */
    public function checkIfRated($projectCourseId = null, $teamId = null, $createdBy = null)
    {
        $result = PeerEvaluationRatingStar::where('peer_evaluation_rating_stars.project_course_id', $projectCourseId)
        ->where('peer_evaluation_rating_stars.team_id', $teamId)
        ->where('peer_evaluation_rating_stars.created_by', $createdBy)->get();
        return $result;
    }

    /** 
     * rate to student
     * @param object $request
     * @return boolean
     */
    public function rateNow($data)
    {
        try {
            $result =   PeerEvaluationRatingStar::insert($data);
            return $result;
            
        } catch (Throwable $e) {
            return false;
        }
    }

    /** 
     * Get rated peer evaluation
     * @param int $universityUserId, $status
     * @return object $ratedEvaluation
     */
    public function getRatedPeerEvaluation($universityUserId = null, $status, $roleId=null, $semId=null)
    {        
        $peerEvaluation=$temp=$allData=[];
        if($roleId!=1){
            $query = PeerEvaluationStart::with(['peer_evaluations','teams', 'project_courses.projects', 'project_courses'=>function($q){
                $q->with(['courses'=>function($q){
                    $q->with('semesters')->get();
                },])->get();
            }])
            ->join('project_courses', 'project_courses.id', '=', 'peer_evaluation_starts.project_course_id')
            ->join('projects', 'projects.id', '=', 'project_courses.project_id')
            ->where('peer_evaluation_starts.created_by', $universityUserId->id)
            ->join('courses', 'courses.id', '=', 'project_courses.course_id')
            ->where('projects.status', '>', $status);
            if($semId){
                $query->where('courses.semester_id', $semId);
            }            
            $query->groupBy('peer_evaluation_starts.project_course_id');
            $temp = $query->orderBy('peer_evaluation_starts.end_date', 'DESC')->get();
        }else{
            $query = PeerEvaluationStart::select('peer_evaluation_starts.*')->with(['peer_evaluations','teams', 'project_courses.projects', 'project_courses'=>function($q){
                $q->with(['courses'=>function($q){
                    $q->with('semesters')->get();
                },])->get();
            }])
            ->join('project_courses', 'project_courses.id', '=', 'peer_evaluation_starts.project_course_id')
            ->join('projects', 'projects.id', '=', 'project_courses.project_id')
            ->join('courses', 'courses.id', '=', 'project_courses.course_id')
            ->where('projects.status', '>', $status);
            if($semId){
                $query->where('courses.semester_id', $semId);
            }
            
            $query->groupBy('peer_evaluation_starts.project_course_id');
            $temp = $query->orderBy('peer_evaluation_starts.end_date', 'DESC')->get();
        }
        //dump($temp);
        if(count($temp)>0){
            $i=0;
            foreach($temp as $key => $eval){
                $allData[$key] = PeerEvaluationStart::where('peer_evaluation_starts.project_course_id', $eval['project_course_id'])->get();
                // $peerEvaluation[$key] = $this->getTeamDataForEval($eval['team_id'], $status);
                // $peerEvaluation[$key]['criteria'] = $this->getEvaluationCriteria($eval['peer_evaluation_id']);

                // $peerEvaluation[$key]['students'] = $this->getTeamStudents($eval['team_id'], $universityUserId->id, $roleId);

                // $peerEvaluation[$key]['rated'] = $this->ratedUsers($eval['project_course_id'], $eval['team_id'], $peerEvaluation[$key]['students'], count($peerEvaluation[$key]['criteria']));
                //$peerEvaluation[$key]['average'] = $this->ratingAverage($eval['project_course_id'], $eval['team_id']);
                $i++;
            }
            foreach($allData as $key => $evals){
                foreach($evals as $key1 => $evalss){
                $peerEvaluation[$evalss['project_course_id']]['data'] = $this->getTeamDataForEval($evalss['team_id'], $status);
                $peerEvaluation[$evalss['project_course_id']]['data']['criteria'] = $this->getEvaluationCriteria($evalss['peer_evaluation_id']);
    
                $peerEvaluation[$evalss['project_course_id']]['ratting'][$evalss['team_id']]['students'] = $this->getTeamStudents($evalss['team_id'], $universityUserId->id, $roleId);
                $peerEvaluation[$evalss['project_course_id']]['ratting'][$evalss['team_id']]['team'] = $this->getTeamDataForEval($evalss['team_id'], $status);
                $peerEvaluation[$evalss['project_course_id']]['ratting'][$evalss['team_id']]['rated'] = $this->ratedUsers($evalss['project_course_id'], $evalss['team_id'], $peerEvaluation[$evalss['project_course_id']]['ratting'][$evalss['team_id']]['students'], count($peerEvaluation[$evalss['project_course_id']]['data']['criteria']));
                $peerEvaluation[$evalss['project_course_id']]['givenRatting'] = $this->getGivenRatting($evalss['project_course_id']);
                $peerEvaluation[$evalss['project_course_id']]['totalRatting'] = $this->getTotalRatting($evalss['project_course_id'], count($peerEvaluation[$evalss['project_course_id']]['data']['criteria']));
                $peerEvaluation[$evalss['project_course_id']]['totalStudent'] = $this->getTotalStudent($evalss['project_course_id']);;
                }
            }
        }else{
            $peerEvaluation=[];
        }
        //dump($peerEvaluation);
        
        //dd($peerEvaluation);
        return $peerEvaluation;
    }

    public function getTotalRatting($pId = null, $peerEval = null)
    {
        $array=[];
        $students = Team::with(['team_students'])->where('teams.project_course_id', $pId)->get();
        foreach($students as $key => $student){
            foreach($student['team_students'] as $key1 => $stu){
                $array[]=$stu;
            }
        }
        return count($array)*$peerEval;
    }

    public function getTotalStudent($pId = null)
    {
        $array=[];
        $students = Team::with(['team_students'])->where('teams.project_course_id', $pId)->get();
        foreach($students as $key => $student){
            foreach($student['team_students'] as $key1 => $stu){
                $array[]=$stu;
            }
        }
        //dump($array);
        return count($array);
    }

    /**
     * @description get total ratting for project course
     * @param int $projectCourseId
     * @return Array
     */
    public function getGivenRatting($projectCourseId = null){
        $data = PeerEvaluationRatingStar::where('project_course_id', $projectCourseId)->count();
        return $data;
    }

    /**
     * @description rated users
     * @param int $projectCourseId, $teamId, $count, date $createdBy
     * @return Array
     */
    public function ratedUsers($projectCourseId = null, $teamId = null, $createdBy = null, $count)
    {
        $datas = $result = [];
        foreach($createdBy as $key => $data){
            $data = PeerEvaluationRatingStar::with(['rated_to_user.university_users', 'rated_by_user.university_users'])
            ->where('peer_evaluation_rating_stars.project_course_id', $projectCourseId)
            //->where('peer_evaluation_rating_stars.team_id', $teamId)
            ->where('peer_evaluation_rating_stars.created_by', $data->rate_by)->orderBy('created_by', 'asc')->get();
            if(count($data) > 0){
                $result[] = $data;
            }
        }          
        $object = [];
        foreach($result as $key => $item){
            if(count($item) == $count){
                $datas[$key][]=json_decode(json_encode($item), true);
            }else{
                if(count($item) > $count){
                    $arr = json_decode($item,true);
                    $array[] = array_chunk($arr, $count);
                    $object = json_decode(json_encode($array), true);
                }else{
                    $object = [];
                }
            }
        }
        $result = array_merge($datas, $object);
        return $result;
    }

    /**
     * @description Average rating
     * @param int $teamId, $projectCourseId
     * @return object|boolean
     */
    public function ratingAverage($projectCourseId = null, $teamId = null)
    {
        $result = PeerEvaluationRatingStar::where('peer_evaluation_rating_stars.project_course_id', $projectCourseId)
            ->where('peer_evaluation_rating_stars.team_id', $teamId)
            ->sum('rate');
        
        $total = PeerEvaluationRatingStar::where('peer_evaluation_rating_stars.project_course_id', $projectCourseId)
            ->where('peer_evaluation_rating_stars.team_id', $teamId)
            ->select('rate')->get();
        if(count($total) > 0){
            return $result/count($total);
        }else{
            return false;
        }
    }

    /** 
     * active template
     * @param int $id
     * @return boolean
     */
    public  function activeTemplate($id)
    {
        DB::table('peer_evaluations')->where('id', $id)->update(['is_active' => 1]);
        return true;
    }

    /**
     * function for get all deactivated feedbacks
     * @retrun object
     */
    public function deactivatedPeerEvaluations(){
        $loginUserId=Auth::user()->id;
		$peerEvaluations =  PeerEvaluation::with('usercreated_by.university_users', 'userupdated_by.university_users')->select('peer_evaluations.id', 'peer_evaluations.name', 'peer_evaluations.description', 'peer_evaluations.is_active', 'peer_evaluations.created_by', 'peer_evaluations.created_at', 'peer_evaluations.updated_by', 'peer_evaluations.updated_at')
        //->leftJoin('evaluation_questions', 'evaluation_questions.evaluation_id', '=', 'evaluations.id')
        ->where('is_active', 0)->orderBy('peer_evaluations.id', 'ASC')->get();
		return $peerEvaluations;
	}

    /**
     * @Get Peer Evaluation Details
     * @param int $ProjectCourseId
     * @return object
     */
    public function getEvalData($ProjectCourseId = null)
    {
        $result = PeerEvaluationStart::where('peer_evaluation_starts.project_course_id', $ProjectCourseId)->first();
        return $result;
    }

    /** 
     * Update Peer Evaluation Data
     * @param object $request
     * @return boolean
     */
    public  function updatePeerEvaluation($request)
    {
        $start = explode('-', $request->start_date);
        $startDate = $start[2].'-'.$start[0].'-'.$start[1];
        $end = explode('-', $request->end_date);
        $endDate = $end[2].'-'.$end[0].'-'.$end[1];
        
        $data['peer_evaluation_id']=$request->peer_evaluation_id;
        $data['start_date']=$startDate;
        $data['end_date']=$endDate;
        $data['project_course_id']=$request->project_course_id;
        $data['updated_by']=$request->updated_by;
        $data['updated_at']=$request->updated_at;
        
        PeerEvaluationStart::where('project_course_id', $request->project_course_id)->update($data);
        return true;
    }

    /**
     * Get the all peer evaluation
     * @return void
     */
    public function getAllPeerEvaluation($semester_id)
    {
        if(Auth::user()->role_id == '3'){
            $peerEvaluation = PeerEvaluationStart::join('project_courses', 'project_courses.id', 'peer_evaluation_starts.project_course_id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->where('courses.semester_id', $semester_id)
            ->where('courses.faculty_id', Auth::user()->university_users->id)
            ->where('peer_evaluation_starts.created_by', Auth::user()->university_users->id)->count();
        }else{
            $peerEvaluation = PeerEvaluationStart::join('project_courses', 'project_courses.id', 'peer_evaluation_starts.project_course_id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->where('courses.semester_id', $semester_id)->count();
        }
        return $peerEvaluation;
    }

    /**
     * Get the all completed peer evaluation
     * @return void
     */
    public function getAllCompletedPeerEvaluation($semester_id)
    {
        if(Auth::user()->role_id == '3'){
            $peerEvaluation=$temp=$allData=[];
            $query = PeerEvaluationStart::with(['peer_evaluations','teams', 'project_courses.projects', 'project_courses'=>function($q){
                $q->with(['courses'=>function($q){
                    $q->with('semesters')->get();
                },])->get();
            }])
            ->join('project_courses', 'project_courses.id', '=', 'peer_evaluation_starts.project_course_id')
            ->join('projects', 'projects.id', '=', 'project_courses.project_id')
            ->join('courses', 'courses.id', '=', 'project_courses.course_id')
            ->where('peer_evaluation_starts.created_by', Auth::user()->university_users->id)
            ->where('courses.faculty_id', Auth::user()->university_users->id)
            ->where('projects.status', '>', 1)
            ->where('courses.semester_id', $semester_id);
                        
            $query->groupBy('peer_evaluation_starts.project_course_id');
            $temp = $query->orderBy('peer_evaluation_starts.end_date', 'DESC')->get();
            //dd($temp);
            // $completedPeerEvaluation = PeerEvaluationRatingStar::join('project_courses', 'project_courses.id', 'peer_evaluation_rating_stars.project_course_id')
            // ->join('courses', 'courses.id', 'project_courses.course_id')
            // ->where('courses.semester_id', $semester_id)->groupBy('peer_evaluation_id')
            // ->where('courses.faculty_id', Auth::user()->university_users->id)->count();
        }else{
            $query = PeerEvaluationStart::select('peer_evaluation_starts.*')->with(['peer_evaluations','teams', 'project_courses.projects', 'project_courses'=>function($q){
                $q->with(['courses'=>function($q){
                    $q->with('semesters')->get();
                },])->get();
            }])
            ->join('project_courses', 'project_courses.id', '=', 'peer_evaluation_starts.project_course_id')
            ->join('projects', 'projects.id', '=', 'project_courses.project_id')
            ->join('courses', 'courses.id', '=', 'project_courses.course_id')
            ->where('projects.status', '>', 1)
            ->where('courses.semester_id', $semester_id);
            
            $query->groupBy('peer_evaluation_starts.project_course_id');
            $temp = $query->orderBy('peer_evaluation_starts.end_date', 'DESC')->get();
            // $completedPeerEvaluation = PeerEvaluationRatingStar::join('project_courses', 'project_courses.id', 'peer_evaluation_rating_stars.project_course_id')
            // ->join('courses', 'courses.id', 'project_courses.course_id')
            // ->where('courses.semester_id', $semester_id)->groupBy('peer_evaluation_id')->count();
        }
        
        if(count($temp)>0){
            $i=0;
            foreach($temp as $key => $eval){
                $allData[$key] = PeerEvaluationStart::where('peer_evaluation_starts.project_course_id', $eval['project_course_id'])->get();
                $i++;
            }
            foreach($allData as $key => $evals){
                foreach($evals as $key1 => $evalss){
                $peerEvaluation[$evalss['project_course_id']]['data'] = $this->getTeamDataForEval($evalss['team_id'], 1);
                $peerEvaluation[$evalss['project_course_id']]['data']['criteria'] = $this->getEvaluationCriteria($evalss['peer_evaluation_id']);
    
                $peerEvaluation[$evalss['project_course_id']]['ratting'][$evalss['team_id']]['students'] = $this->getTeamStudents($evalss['team_id'], Auth::user()->university_users->id, Auth::user()->role_id);
                $peerEvaluation[$evalss['project_course_id']]['ratting'][$evalss['team_id']]['team'] = $this->getTeamDataForEval($evalss['team_id'], 1);
                $peerEvaluation[$evalss['project_course_id']]['ratting'][$evalss['team_id']]['rated'] = $this->ratedUsers($evalss['project_course_id'], $evalss['team_id'], $peerEvaluation[$evalss['project_course_id']]['ratting'][$evalss['team_id']]['students'], count($peerEvaluation[$evalss['project_course_id']]['data']['criteria']));
                $peerEvaluation[$evalss['project_course_id']]['givenRatting'] = $this->getGivenRatting($evalss['project_course_id']);
                $peerEvaluation[$evalss['project_course_id']]['totalRatting'] = $this->getTotalRatting($evalss['project_course_id'], count($peerEvaluation[$evalss['project_course_id']]['data']['criteria']));
                $peerEvaluation[$evalss['project_course_id']]['totalStudent'] = $this->getTotalStudent($evalss['project_course_id']);;
                }
            }
        }else{
            $peerEvaluation=[];
        }
        $completedPeerEvaluation = 0;
        if($peerEvaluation){
            foreach($peerEvaluation as $key => $completed){
                if($completed['givenRatting'] == $completed['totalRatting']){
                    $completedPeerEvaluation++;
                }

            }
        }
        
        return $completedPeerEvaluation;
    }

    /**
     * @Check if peer evaluation is already done by student
     * @param int $ProjectCourseId
     * @return boolean
     */
    public function isEvaluationDone($projectCourseId)
    {
        $count = PeerEvaluationRatingStar::where('peer_evaluation_rating_stars.project_course_id', $projectCourseId)
        ->select('*')->count();
        if($count > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @Get students list
     * @param int $projectCourseId
     * @return boolean
     */
    public function getStudentSendReminder($projectCourseId = null)
    {
        $array=[];
        $students = Team::with(['team_students_details'])
        ->where('teams.is_deleted', 0)
        ->where('teams.project_course_id', $projectCourseId)->get();
        
        foreach($students as $key => $student){
            foreach($student['team_students_details'] as $key1 => $stu){
                $array[$student['id']][$key1]['team_id']=$student->id;
                $array[$student['id']][$key1]['id']=$stu->student_id;
                $array[$student['id']][$key1]['name']=ucfirst($stu->first_name).' '.ucfirst($stu->last_name);
                $array[$student['id']][$key1]['email']=$stu->email;
            }
        }
        
        return $array;
    }

     /**
     * @check if peer evaluation rated
     * @param int $projectCourseId, $studentId
     * @return boolean
     */
    public function checkPeerEvaluation($projectCourseId = null, $studentId = null, $team = null)
    {
        $count = PeerEvaluationRatingStar::where('peer_evaluation_rating_stars.project_course_id', $projectCourseId)
        ->where('peer_evaluation_rating_stars.created_by', $studentId)
        ->where('peer_evaluation_rating_stars.team_id', $team)
        ->select('*')->count();
        if($count > 0){
            return false;
        }else{
            return true;
        }
    }
}

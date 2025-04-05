<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Evaluation;
use App\Models\TimeAccount;

class EvaluationService
{

    /**
     * Save or update template
     * @param $request form data
     * @return response object
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
            DB::table('evaluations')
                ->where('id', $request['id'])
                ->update($template);

            $eval_id = $request['id'];
            $msg = 'Client feedback template updated successfully';
        } else {
            $template['created_by'] = $university_user->id;
            $template['created_at'] = $request['created_at'];
            $template['updated_at'] = $request['updated_at'];
            DB::table('evaluations')->insert($template);

            $eval_id = DB::getPdo()->lastInsertId();
            $msg = 'Client feedback template added successfully';
        }

        if (!empty($request['questions'])) {
            $add_milestone_name_arr = $request['questions'];
            $add_milestone_description_arr = $request['question_description'];
            $add_milestone_id_arr = isset($request['evaluation_id']) ? $request['evaluation_id'] : '';

            // $count_new_milestones = count($add_milestone_name_arr);
            $order_counter = 1;
            foreach ($add_milestone_name_arr as $count_milestone => $single_milestone_name) {
                if (empty($add_milestone_id_arr[$count_milestone])) {
                    $insert_milestone['question'] = $single_milestone_name;
                    $insert_milestone['description'] = $add_milestone_description_arr[$count_milestone];
                    $insert_milestone['evaluation_id'] = $eval_id;
                    // $insert_milestone['created_by'] = $university_user->id;
                    $insert_milestone['created_at'] = date('Y-m-d H:i:s');
                    $insert_milestone['order_counter'] = $order_counter;

                    DB::table('evaluation_questions')->insert($insert_milestone);
                } else {
                    $update_milestone['question'] = $single_milestone_name;
                    $update_milestone['description'] = $add_milestone_description_arr[$count_milestone];
                    //     $update_milestone['updated_by'] = $university_user->id;
                    $update_milestone['updated_at'] = date('Y-m-d H:i:s');
                    $update_milestone['order_counter'] = $order_counter;

                    DB::table('evaluation_questions')
                        ->where('id', $add_milestone_id_arr[$count_milestone])
                        ->update($update_milestone);

                    if (isset($request['milestone_deleted'])) {
                        $del_arr = explode(",", $request['milestone_deleted']);
                        foreach ($del_arr as $del_id) {
                            DB::table('evaluation_questions')
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
     * @return Array
     */
    public function search_all_template_counts($request)
    {
        $keywords = $request->search['value'];
        $queries = DB::table('evaluations')->select('evaluations.id')->where('evaluations.is_active', '1');
        //->where('evaluations.is_deleted', '0');
        // ->where('users.role_id',  '=', '2')
        // ->join('roles', 'roles.id', '=', 'users.role_id');

        $keywords = trim($keywords);
        if (!empty($keywords)) {
            $search_words =  explode(' ', $keywords);
            $firstword = $search_words[0];
            $lastword = @$search_words[1];

            if (empty($lastword)) {
                $queries->Where(function ($query) use ($firstword) {
                    $query->where('evaluations.name', 'like', '%' . $firstword . '%')
                        ->orWhere('evaluations.description', 'like', '%' . $firstword . '%');
                    // ->orWhere('users.email', 'like', '%' . $firstword . '%')
                    //  ->orWhere('users.user_name', 'like', '%' . $firstword . '%')
                    //   ->orWhere('roles.name', 'like', '%' . $firstword . '%');
                });
            } else {
                $queries->Where(function ($query) use ($firstword, $lastword) {
                    $query->where('evaluations.name', 'like', '%' . $firstword . '%')
                        ->Where('evaluations.name', 'like', '%' . $lastword . '%')
                        ->orWhere('evaluations.description', 'like', '%' . $firstword . ' ' . $lastword . '%');
                });
            }
        }


        return $queries->count();
    }

    /**
     * List all templates.
     * @param $request(object)
     * @return  Array
     */

    public function alltemplates($request)
    {
        $keywords = trim($request->search['value']);
        $start = $request->start;
        $limit = $request->length;
        $order_index = $request->order[0]['column'];
        $order_by = $request->columns[$order_index]['data'];
        $sort_by = $request->order[0]['dir'];
        $queries =  Evaluation::with('usercreated_by.university_users', 'userupdated_by.university_users')->select('evaluations.id', 'evaluations.name', 'evaluations.description', 'evaluations.is_active', 'evaluations.is_deleted', 'evaluations.is_assigned', 'evaluations.created_by', 'evaluations.created_at', 'evaluations.updated_by', 'evaluations.updated_at', DB::raw('COUNT(evaluation_questions.id) as no_ques'))
           //->where('evaluations.is_deleted', '0')
           ->where(function($query){
                //$query->whereNull('evaluation_questions.id');
                $query->Where('evaluation_questions.is_deleted', '0');
                
            })
            ->where('evaluations.is_active', '1')
            //->where('users.role_id',  '=', '2')
            ->leftJoin('evaluation_questions', 'evaluation_questions.evaluation_id', '=', 'evaluations.id');
        // ->where('users.role_id',  '=', '2')

        $keywords = trim($keywords);
        if (!empty($keywords)) {
            $search_words =  explode(' ', $keywords);
            $firstword = $search_words[0];
            $lastword = @$search_words[1];

            if (empty($lastword)) {
                $queries->Where(function ($query) use ($firstword) {
                    $query->where('evaluations.name', 'like', '%' . $firstword . '%')
                        ->orWhere('evaluations.description', 'like', '%' . $firstword . '%');
                    // ->orWhere('users.email', 'like', '%' . $firstword . '%')
                    //  ->orWhere('users.user_name', 'like', '%' . $firstword . '%')
                    //   ->orWhere('roles.name', 'like', '%' . $firstword . '%');
                });
            } else {
                $queries->Where(function ($query) use ($firstword, $lastword) {
                    $query->where('evaluations.name', 'like', '%' . $firstword . '%')
                        ->Where('evaluations.name', 'like', '%' . $lastword . '%')
                        ->orWhere('evaluations.description', 'like', '%' . $firstword . ' ' . $lastword . '%');
                });
            }
        }
        // if (!empty($request->course_id)) {
        //     $queries->Where('users.role_id', '4');
        // }
        // return $queries = $queries->Where('users.role_id', '3')->orderBy($order_by, $sort_by)
        return $queries = $queries->groupBy('evaluations.id')->orderBy($order_by, $sort_by)
            ->limit($limit, $start)
            ->get();
    }

    /**
     * fetch the questions saved in a template
     * @param $request(object)
     * @return object
     */
    public function get_all_questions_with_template($request)
    {
        return DB::table('evaluation_questions')->select('id', 'question','description')
            ->Where('evaluation_id', $request->ques_id)
            ->Where('is_deleted', '0')
            ->orderBy('order_counter', 'asc')->get();
    }

    /** 
     * Delete Role
     * @param int $id
     * @return boolean
     */
    public  function deleteTemplate($id)
    {
        DB::table('evaluations')->where('id', $id)->update(['is_active' => 0]);
        return true;
    }

    /**
     * Save or update time
     * @param $request form data
     * @return string
     */
    public function update_time($request, $user_id)
    {
       $insert = [];
        $university_user =  DB::table('university_users')->select('id')
            ->Where('user_id', $user_id)->first();
        //dd($request);
        $add_title = $request['title'];
        $add_description = $request['description'];

        if(!isset($request['id'])){
            $insert['title'] = $add_title;
            $insert['description'] = $add_description;
            $insert['created_by'] = $university_user->id;
            $insert['updated_by'] = $university_user->id;
            $insert['created_at'] = $request['created_at'];
            $insert['updated_at'] = $request['updated_at'];
        }else{
            $id = $request['id'];
            $update['title'] = $add_title;
            $update['description'] = $add_description;
            $update['updated_by'] = $university_user->id;
            $update['updated_at'] = $request['updated_at'];
            DB::table('time_accounts')
                    ->where('id', $id)
                    ->update($update);
        }
        
        if(isset($insert)){
            DB::table('time_accounts')->insert($insert);
            if($insert){
                $msg = 'Time category added successfully.';
            }else{
                $msg = 'Time category updated successfully.';
            }
        }else{
            $msg = 'Unable to add time account.';
        }
        return $msg;
    }

    /**
     * @description Function get count of filtered time accounts
     * @param $request(object)
     * @return Array
     */
    public function search_all_time_account_counts($request)
    {
        $keywords = $request->search['value'];
        $queries = DB::table('time_accounts')->select('time_accounts.id');
        //->where('time_accounts.is_deleted', '0');
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
     * @param $request(object)
     * @return Array
     */

    public function allTimeAccounts($request)
    {
        $keywords = trim($request->search['value']);
        $start = $request->start;
        $limit = $request->length;
        $order_index = $request->order[0]['column'];
        $order_by = $request->columns[$order_index]['data'];
        $sort_by = $request->order[0]['dir'];
        $queries =  TimeAccount::with('usercreated_by.university_users', 'userupdated_by.university_users')->select('time_accounts.id', 'time_accounts.title', 'time_accounts.description', 'time_accounts.is_active', 'time_accounts.is_deleted', 'time_accounts.is_assigned', 'time_accounts.created_by', 'time_accounts.created_at', 'time_accounts.updated_by', 'time_accounts.updated_at')->where('time_accounts.is_active', '1');
           //->where('time_accounts.is_deleted', '0');
        //    ->where(function($query){
        //         $query->whereNull('time_categories.id');
        //         $query->orWhere('time_categories.is_deleted', '0');
                
        //     })
        //    ->where('evaluation_questions.is_deleted', '0')
            //->where('users.role_id',  '=', '2')
         //   ->leftJoin('time_categories', 'time_categories.time_account_id', '=', 'time_accounts.id');
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
        $queries = $queries->groupBy('time_accounts.id')->orderBy($order_by, $sort_by)
            ->limit($limit, $start)
            ->get();
        return $queries;
    }

     /**
     * fetch the milestones saved ina pm plans
     * @param $request(object)
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
    public  function deleteTime($id, $dateTime)
    {
        DB::table('time_accounts')->where('id', $id)->update(['is_active' => 0, 'updated_by' => Auth::user()->university_users->id, 'updated_at' => $dateTime]);
        return true;
    }

    /** 
     * Delete client review template
     * @param int $id
     * @return boolean
     */
    public  function activeTemplate($id)
    {
        DB::table('evaluations')->where('id', $id)->update(['is_active' => 1]);
        return true;
    }

    /** 
     * Activate Time Category
     * @param int $id
     * @return boolean
     */
    public  function activeTime($id, $dateTime)
    {
        DB::table('time_accounts')->where('id', $id)->update(['is_active' => 1, 'updated_by' => Auth::user()->university_users->id, 'updated_at' => $dateTime]);
        return true;
    }

    /** 
     * Check if time category already in storage for same title and created by user
     * @param $request
     * @return boolean
     */
    public  function uniqueTimeCategoryTitle($request)
    {
        //dd($request->title);
        $checkTimeCat = DB::table('time_accounts')->select('title')->where('title', $request['title'])->where('created_by', Auth::user()->university_users->id)->first();
        if ( !empty($checkTimeCat) ) {
            return response()->json(false);	
        } else {
            return response()->json(true);
        }
    }

    /**
     * function for get all deactivated feedbacks
     * @retrun object
     */
    public function deactivatedFeedbacks(){
        $loginUserId=Auth::user()->id;
		$feedbacks =  Evaluation::with('usercreated_by.university_users', 'userupdated_by.university_users')->select('evaluations.id', 'evaluations.name', 'evaluations.description', 'evaluations.is_active', 'evaluations.created_by', 'evaluations.created_at', 'evaluations.updated_by', 'evaluations.updated_at')
        ->where('is_active', 0)->orderBy('evaluations.id', 'ASC')->get();
		return $feedbacks;
	}

    /**
     * function for get all deactivated time spends
     * @retrun object
     */
    public function deactivatedTimeSpend(){
        $loginUserId=Auth::user()->id;
		$timeAccounts =  TimeAccount::with('usercreated_by.university_users', 'userupdated_by.university_users')->select('time_accounts.id', 'time_accounts.title', 'time_accounts.description', 'time_accounts.is_active', 'time_accounts.created_by', 'time_accounts.created_at', 'time_accounts.updated_by', 'time_accounts.updated_at')
        ->where('is_active', 0)->orderBy('time_accounts.id', 'ASC')->get();
		return $timeAccounts;
	}
}

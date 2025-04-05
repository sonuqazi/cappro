<?php

namespace App\Services;

use App\Models\MoneyAccount;
use App\Models\MoneyCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;


class MoneyService
{
    /**
     * @description Peer Evalution
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
     * @param $request form data
     * @return
     */
    public function update_category_template($request, $user_id)
    {
        //dd($request);
        $university_user =  DB::table('university_users')->select('id')
            ->Where('user_id', $user_id)->first();

        $template = array(
            'title' => $request['title'],
            'description' => $request['description'],
        );
        // dd($template);
        if (!empty($request['id'])) {
            // dd('here');
            $template['updated_by'] = $university_user->id;
            $template['updated_at'] = $request['updated_at'];
            DB::table('money_categories')
                ->where('id', $request['id'])
                ->update($template);

            $eval_id = $request['id'];
            $msg = 'Money category updated successfully';
        } else {
            $template['created_by'] = $university_user->id;
            $template['created_at'] = $request['created_at'];
            $template['updated_at'] = $request['updated_at'];
            DB::table('money_categories')->insert($template);

            $eval_id = DB::getPdo()->lastInsertId();
            $msg = 'Money category added successfully';
        }

        if (!empty($request['title_detail'])) {
            $add_milestone_name_arr = $request['title_detail'];
            $add_milestone_description_arr = $request['title_description'];
            $add_milestone_id_arr = isset($request['money_category_id']) ? $request['money_category_id'] : '';

            // $count_new_milestones = count($add_milestone_name_arr);
            $order_counter = 1;
            foreach ($add_milestone_name_arr as $count_milestone => $single_milestone_name) {
                if (empty($add_milestone_id_arr[$count_milestone])) {
                    $insert_milestone['title'] = $single_milestone_name;
                    $insert_milestone['description'] = $add_milestone_description_arr[$count_milestone];
                    $insert_milestone['money_category_id'] = $eval_id;
                    //$insert_milestone['created_by'] = $university_user->id;
                    $insert_milestone['created_at'] = $request['created_at'];
                    $insert_milestone['order_counter'] = $order_counter;

                    DB::table('money_category_details')->insert($insert_milestone);
                } else {
                    $update_milestone['title'] = $single_milestone_name;
                    $update_milestone['description'] = $add_milestone_description_arr[$count_milestone];
                    //$update_milestone['updated_by'] = $university_user->id;
                    $update_milestone['updated_at'] = $request['updated_at'];
                    $update_milestone['order_counter'] = $order_counter;

                    DB::table('money_category_details')
                        ->where('id', $add_milestone_id_arr[$count_milestone])
                        ->update($update_milestone);

                    if (isset($request['milestone_deleted'])) {
                        $del_arr = explode(",", $request['milestone_deleted']);
                        foreach ($del_arr as $del_id) {
                            DB::table('money_category_details')
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
     * Save or update template
     * @param object $request
     * @return string
     */
    public function update_account_template($request, $user_id)
    {
        //dd($request);
        $university_user =  DB::table('university_users')->select('id')
            ->Where('user_id', $user_id)->first();

        $template = array(
            'title' => $request['title'],
            'description' => $request['description'],
        );
        // dd($template);
        if (!empty($request['id'])) {
            // dd('here');
            $template['updated_by'] = $university_user->id;
            $template['updated_at'] = $request['updated_at'];
            //dd($request['id']);
            MoneyAccount::where('id', $request['id'])
                ->update($template);

            $eval_id = $request['id'];
            $msg = 'Money account updated successfully';
        } else {
            $template['created_by'] = $university_user->id;
            $template['created_at'] = $request['created_at'];
            $template['updated_at'] = $request['updated_at'];
            DB::table('money_accounts')->insert($template);

            $eval_id = DB::getPdo()->lastInsertId();
            $msg = 'Money account added successfully';
        }

        if (!empty($request['title_detail'])) {
            $add_milestone_name_arr = $request['title_detail'];
            $add_milestone_description_arr = $request['title_description'];
            $add_milestone_id_arr = isset($request['money_account_id']) ? $request['money_account_id'] : '';

            // $count_new_milestones = count($add_milestone_name_arr);
            $order_counter = 1;
            foreach ($add_milestone_name_arr as $count_milestone => $single_milestone_name) {
                if (empty($add_milestone_id_arr[$count_milestone])) {
                    $insert_milestone['title'] = $single_milestone_name;
                    $insert_milestone['description'] = $add_milestone_description_arr[$count_milestone];
                    //$insert_milestone['money_account_id'] = $eval_id;
                    $insert_milestone['created_by'] = $university_user->id;
                    $insert_milestone['created_at'] = $request['created_at'];
                    $insert_milestone['updated_at'] = $request['updated_at'];
                    //$insert_milestone['order_counter'] = $order_counter;

                    DB::table('money_accounts')->insert($insert_milestone);
                } else {
                    $update_milestone['title'] = $single_milestone_name;
                    $update_milestone['description'] = $add_milestone_description_arr[$count_milestone];
                    $update_milestone['updated_by'] = $university_user->id;
                    $update_milestone['updated_at'] = $request['updated_at'];
                    //$update_milestone['order_counter'] = $order_counter;

                    DB::table('money_accounts')
                        ->where('id', $add_milestone_id_arr[$count_milestone])
                        ->update($update_milestone);

                    if (isset($request['milestone_deleted'])) {
                        $del_arr = explode(",", $request['milestone_deleted']);
                        foreach ($del_arr as $del_id) {
                            DB::table('money_accounts')
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
    public function search_all_category_counts($request)
    {
        $keywords = $request->search['value'];
        $queries = DB::table('money_categories')->select('money_categories.id');
        //->where('money_categories.is_deleted', '0');

        $keywords = trim($keywords);
        if (!empty($keywords)) {
            $search_words =  explode(' ', $keywords);
            $firstword = $search_words[0];
            $lastword = @$search_words[1];

            if (empty($lastword)) {
                $queries->Where(function ($query) use ($firstword) {
                    $query->where('money_categories.title', 'like', '%' . $firstword . '%')
                        ->orWhere('money_categories.description', 'like', '%' . $firstword . '%');
                });
            } else {
                $queries->Where(function ($query) use ($firstword, $lastword) {
                    $query->where('money_categories.title', 'like', '%' . $firstword . '%')
                        ->Where('money_categories.title', 'like', '%' . $lastword . '%')
                        ->orWhere('money_categories.description', 'like', '%' . $firstword . ' ' . $lastword . '%');
                });
            }
        }


        return $queries->count();
    }

    /**
     * @description Function get count of filtered templates
     * @param object $request
     * @return Array
     */
    public function search_all_account_counts($request)
    {
        $keywords = $request->search['value'];
        $queries = DB::table('money_accounts')->select('money_accounts.id');
        //->where('money_accounts.is_deleted', '0');

        $keywords = trim($keywords);
        if (!empty($keywords)) {
            $search_words =  explode(' ', $keywords);
            $firstword = $search_words[0];
            $lastword = @$search_words[1];

            if (empty($lastword)) {
                $queries->Where(function ($query) use ($firstword) {
                    $query->where('money_accounts.title', 'like', '%' . $firstword . '%')
                        ->orWhere('money_accounts.description', 'like', '%' . $firstword . '%');
                });
            } else {
                $queries->Where(function ($query) use ($firstword, $lastword) {
                    $query->where('money_accounts.title', 'like', '%' . $firstword . '%')
                        ->Where('money_accounts.title', 'like', '%' . $lastword . '%')
                        ->orWhere('money_accounts.description', 'like', '%' . $firstword . ' ' . $lastword . '%');
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

    public function allcategory($request)
    {
        $keywords = trim($request->search['value']);
        $start = $request->start;
        $limit = $request->length;
        $order_index = $request->order[0]['column'];
        $order_by = $request->columns[$order_index]['data'];
        $sort_by = $request->order[0]['dir'];
        $queries =  MoneyCategory::with('usercreated_by.university_users', 'userupdated_by.university_users')->select('money_categories.*', DB::raw('COUNT(money_category_details.id) as no_rating'))
           //->where('money_categories.is_deleted', '0')
           ->where(function($query){
                $query->whereNull('money_category_details.id');
                $query->orWhere('money_category_details.is_deleted', '0');
                
            })
            ->where('money_categories.is_active', '1')
            ->leftJoin('money_category_details', 'money_category_details.money_category_id', '=', 'money_categories.id');
        
        $keywords = trim($keywords);
        if (!empty($keywords)) {
            $search_words =  explode(' ', $keywords);
            $firstword = $search_words[0];
            $lastword = @$search_words[1];

            if (empty($lastword)) {
                $queries->Where(function ($query) use ($firstword) {
                    $query->where('money_categories.title', 'like', '%' . $firstword . '%')
                        ->orWhere('money_categories.description', 'like', '%' . $firstword . '%');
                });
            } else {
                $queries->Where(function ($query) use ($firstword, $lastword) {
                    $query->where('money_categories.title', 'like', '%' . $firstword . '%')
                        ->Where('money_categories.title', 'like', '%' . $lastword . '%')
                        ->orWhere('money_categories.description', 'like', '%' . $firstword . ' ' . $lastword . '%');
                });
            }
        }
        $queries = $queries->groupBy('money_categories.id')->orderBy($order_by, $sort_by)
            ->limit($limit, $start)
            ->get();
        return $queries;
    }

    /**
     * List all templates.
     * @param object $request
     * @return $request
     */

    public function allaccount($request)
    {
        $keywords = trim($request->search['value']);
        $start = $request->start;
        $limit = $request->length;
        $order_index = $request->order[0]['column'];
        $order_by = $request->columns[$order_index]['data'];
        $sort_by = $request->order[0]['dir'];
        $queries =  MoneyAccount::with('usercreated_by.university_users', 'userupdated_by.university_users')->select('money_accounts.*', DB::raw('COUNT(money_account_details.id) as no_rating'))
           //->where('money_accounts.is_deleted', '0')
           ->where(function($query){
                $query->whereNull('money_account_details.id');
                $query->orWhere('money_account_details.is_deleted', '0');
                
            })
            ->where('money_accounts.is_active', '1')
            ->leftJoin('money_account_details', 'money_account_details.money_account_id', '=', 'money_accounts.id');
        
        $keywords = trim($keywords);
        if (!empty($keywords)) {
            $search_words =  explode(' ', $keywords);
            $firstword = $search_words[0];
            $lastword = @$search_words[1];

            if (empty($lastword)) {
                $queries->Where(function ($query) use ($firstword) {
                    $query->where('money_accounts.title', 'like', '%' . $firstword . '%')
                        ->orWhere('money_accounts.description', 'like', '%' . $firstword . '%');
                });
            } else {
                $queries->Where(function ($query) use ($firstword, $lastword) {
                    $query->where('money_accounts.title', 'like', '%' . $firstword . '%')
                        ->Where('money_accounts.title', 'like', '%' . $lastword . '%')
                        ->orWhere('money_accounts.description', 'like', '%' . $firstword . ' ' . $lastword . '%');
                });
            }
        }
        $queries = $queries->groupBy('money_accounts.id')->orderBy($order_by, $sort_by)
            ->limit($limit, $start)
            ->get();
        return $queries;
    }

    /**
     * fetch the questions saved in a template
     * @param object $request 
     * @return object
     */
    public function get_all_money_category_with_template($request)
    {
        return DB::table('money_category_details')->select('id', 'title','description')
            ->Where('money_category_id', $request->category_id)
            ->Where('is_deleted', '0')
            ->orderBy('order_counter', 'asc')->get();
    }

    /**
     * fetch the questions saved in a template
     * @param  object $request
     * @return
     */
    public function get_all_money_account_with_template($request)
    {
        return DB::table('money_account_details')->select('id', 'title','description')
            ->Where('money_account_id', $request->category_id)
            ->Where('is_deleted', '0')
            ->orderBy('order_counter', 'asc')->get();
    }

    /** 
     * Delete Role
     * @param int $id
     * @return boolean
     */
    public  function deleteCategoryTemplate($id, $dateTime)
    {
        DB::table('money_categories')->where('id', $id)->update(['is_active' => 0, 'updated_by' => Auth::user()->university_users->id, 'updated_at' => $dateTime]);
        return true;
    }

    /** 
     * Delete Role
     * @param int $id
     * @return boolean
     */
    public  function deleteAccountTemplate($id, $dateTime)
    {
        DB::table('money_accounts')->where('id', $id)->update(['is_active' => 0, 'updated_by' => Auth::user()->university_users->id, 'updated_at' => $dateTime]);
        return true;
    }

    /**
     * Save or update time
     * @param object $request
     * @return string
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
     * Activate monayCategory
     * @param int $id
     * @return boolean
     */
    public  function activeCategoryTemplate($id)
    {
        DB::table('money_categories')->where('id', $id)->update(['is_active' => 1]);
        return true;
    }

    /** 
     * Active money accout
     * @param int $id
     * @return boolean
     */
    public  function activeAccountTemplate($id)
    {
        DB::table('money_accounts')->where('id', $id)->update(['is_active' => 1]);
        return true;
    }

    /**
     * Check if Money Category Template already exist with same title and created by user
     * @raram $request
     * @return Json
     */
    public function uniqueMoneyCategoryTitle($request)
    {
        $checkMoneyCat = DB::table('money_categories')->where('title',  $request['title'])->where('created_by',  Auth::user()->university_users->id)->first();
        if ( !empty($checkMoneyCat) ) {
            return response()->json(false);	
        } else {
            return response()->json(true);
        }
    }

    /**
     * Check if Money Account Template already exist with same title and created by user
     * @raram $request
     * @return Json
     */
    public function uniqueMoneyAccountTitle($request)
    {
        $checkMoneyCat = DB::table('money_accounts')->where('title',  $request['title'])->where('created_by',  Auth::user()->university_users->id)->first();
        if ( !empty($checkMoneyCat) ) {
            return response()->json(false);	
        } else {
            return response()->json(true);
        }
    }

    /**
     * function for get all deactivated money accounts
     * @retrun object
     */
    public function deactivateMoneyAccounts(){
        $loginUserId=Auth::user()->id;
		$moneyAccountList =  MoneyAccount::with('usercreated_by.university_users', 'userupdated_by.university_users')->select('money_accounts.id', 'money_accounts.title', 'money_accounts.description', 'money_accounts.is_active', 'money_accounts.created_by', 'money_accounts.created_at', 'money_accounts.updated_by', 'money_accounts.updated_at')
        ->where('is_active', 0)->orderBy('money_accounts.id', 'ASC')->get();
		return $moneyAccountList;
	}

    /**
     * function for get all deactivated money accounts
     * @retrun object
     */
    public function deactivateMoneyCategorys(){
        $loginUserId=Auth::user()->id;
		$moneyCategoryList =  MoneyCategory::with('usercreated_by.university_users', 'userupdated_by.university_users')->select('money_categories.id', 'money_categories.title', 'money_categories.description', 'money_categories.is_active', 'money_categories.created_by', 'money_categories.created_at', 'money_categories.updated_by', 'money_categories.updated_at')
        ->where('is_active', 0)->orderBy('money_categories.id', 'ASC')->get();
		return $moneyCategoryList;
	}
}

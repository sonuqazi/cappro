<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MoneyService;
use App\Services\UserProfileService;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;

class MoneyController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage Money Accounts', ['only' => ['addMoneyAccount','destroy_account','active_account','uniqueMoneyAccountTitle', 'all_money_account']]);
        $this->middleware('permission:Manage Money Categories', ['only' => ['addMoneyCategory','destroy_category','active_category','uniqueMoneyCategoryTitle', 'all_money_category']]);
        $this->userProfileObj = new UserProfileService(); // user profile Service object
        $this->moneyObj = new MoneyService(); // user profile Service object
        //$this->uploadFileObj = new UploadFilesService(); // user profile Service object
    }

    /**
     * List of all money categories from storage
     * 
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function all_money_category()
    {
        $user_id = auth()->user()->id;
        $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
        return view('money.category', compact('user_profile_data', 'user_id'));
    }

    /**
     * List of all money accounts from storage
     * 
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function all_money_account()
    {
        $user_id = auth()->user()->id;
        $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
        return view('money.account', compact('user_profile_data', 'user_id'));
    }

    /**
     * Search templates
     * @param \Illuminate\Http\Request  $request
     * @return json
     */
    public function search_all_category(Request $request)
    {

        $user_id = auth()->user()->id;
        if ($request->ajax() && !empty($user_id)) {
            $view_templates = $this->moneyObj->allcategory($request);
            $return['recordsFiltered'] = $return['recordsTotal'] = count($view_templates);
            $return['draw'] = $request->draw;
            $options = '';
            $editOption = '';
            $view_templates = $this->moneyObj->allcategory($request);
            foreach ($view_templates as $key => $temp) {
                
                $editOption = '<a class="edit-template del-active" edit-id = "' . $temp->id . '" >Edit</a> ';
                if($temp->is_active == 1){ $del = '<span class="mx-1">|</span><a class="delete-money-cat del-active" del-id = "'. $temp->id .'" >Deactivate</a>';}else{$del = '<span class="mx-1">|</span><a class="active-money-cat del-active" act-id = "'. $temp->id .'" >Activate</a>';}
                if(Auth::user()->role_id == 1){
                    $options = $editOption.$del;
                }
                if($temp->created_by == Auth::user()->university_users->id){
                    $options = $editOption.$del;
                }
                $data[$key]['title'] = '<span class="edit-temp-name-' . $temp->id . '">' . $temp->title . '</span>';
                $data[$key]['description'] = '<span class="edit-temp-description-' . $temp->id . '">' . $temp->description . '</span> ';
                $data[$key]['total_rating'] = '<span class="edit-total-rating-' . $temp->id . '">' . $temp->no_rating  . '</span>';
                $data[$key]['owner'] = $temp->usercreated_by->university_users->first_name.' '.$temp->usercreated_by->university_users->last_name.'  <br>'.date('F d, Y H:i A', strtotime($temp->created_at)); 
                if(!empty($temp->userupdated_by)){
                    $data[$key]['lastUpdate'] = $temp->userupdated_by->university_users->first_name.' '.$temp->userupdated_by->university_users->last_name.'  <br>'.date('F d, Y H:i A', strtotime($temp->updated_at));
                }else{
                    $data[$key]['lastUpdate'] = $temp->usercreated_by->university_users->first_name.' '.$temp->usercreated_by->university_users->last_name.'  <br>'.date('F d, Y H:i A', strtotime($temp->created_at)); 
                }
                $data[$key]['edit'] = $options; 
                $data[$key]['id'] = $temp->id;
                $options = '';
                $editOption = '';
            }

            $return['data'] = !empty($data) ? $data : array();
            return response()->json($return);
        }
    }

    /**
     * Search templates
     * @param \Illuminate\Http\Request  $request
     * @return json
     */
    public function search_all_account(Request $request)
    {

        $user_id = auth()->user()->id;
        if ($request->ajax() && !empty($user_id)) {
            $view_templates = $this->moneyObj->allaccount($request);
            $return['recordsFiltered'] = $return['recordsTotal'] = count($view_templates);
            $return['draw'] = $request->draw;
            $options = '';
            $editOption = '';

            foreach ($view_templates as $key => $temp) {
                 
                $editOption = '<a class="edit-template del-active" edit-id = "' . $temp->id . '" >Edit</a> ';
                if($temp->is_active == 1){ $del = '<span class="mx-1">|</span> <a class="delete-peer del-active" del-id = "'. $temp->id .'" >Deactivate</a>';}else{$del = '<span class="mx-1">|</span> <a class="active-money-acc del-active" act-id = "'. $temp->id .'" >Activate</a>';}
                if(Auth::user()->role_id == 1){
                    $options = $editOption.$del;
                }
                if($temp->created_by == Auth::user()->university_users->id){
                    $options = $editOption.$del;
                }
                $data[$key]['title'] = '<span class="edit-temp-name-' . $temp->id . '">' . $temp->title . '</span>';
                $data[$key]['description'] = '<span class="edit-temp-description-' . $temp->id . '">' . $temp->description . '</span> ';
                $data[$key]['total_rating'] = '<span class="edit-total-rating-' . $temp->id . '">' . $temp->no_rating  . '</span>';
                $data[$key]['owner'] = $temp->usercreated_by->university_users->first_name.' '.$temp->usercreated_by->university_users->last_name.'  <br>'.date('F d, Y H:i A', strtotime($temp->created_at)); 
                if(!empty($temp->userupdated_by)){
                    $data[$key]['lastUpdate'] = $temp->userupdated_by->university_users->first_name.' '.$temp->userupdated_by->university_users->last_name.'  <br>'.date('F d, Y H:i A', strtotime($temp->updated_at));
                }else{
                    $data[$key]['lastUpdate'] = $temp->usercreated_by->university_users->first_name.' '.$temp->usercreated_by->university_users->last_name.'  <br>'.date('F d, Y H:i A', strtotime($temp->created_at)); 
                }
                $data[$key]['edit'] = $options; 
                $data[$key]['id'] = $temp->id;
                $options = '';
                $editOption = '';
            }

            $return['data'] = !empty($data) ? $data : array();
            return response()->json($return);
        }
    }

    /**
     * @access public
     * @desc Function fetch all questions added in temp
     * @param \Illuminate\Http\Request  $request
     * @return Json
     */
    public function get_all_money_category_detail(Request $request){
        if ($request->ajax()) {
            $user_student_data = $this->moneyObj->get_all_money_category_with_template($request);
        }
        return response()->json($user_student_data);
    }

    /**
     * @access public
     * @desc Function fetch all questions added in temp
     * @param \Illuminate\Http\Request  $request
     * @return Json
     */
    public function get_all_money_account_detail(Request $request){
        if ($request->ajax()) {
            $user_student_data = $this->moneyObj->get_all_money_account_with_template($request);
        }
        return response()->json($user_student_data);
    }

    /**
     * Add template
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function addMoneyCategory(Request $request){
        $data = $request->all();
        $questions = $this->moneyObj->update_category_template($data, auth()->user()->id);
        return redirect()->route('allMoneyCategory')->with('success', $questions);
    }

    /**
     * Add template
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    
    public function addMoneyAccount(Request $request){
        $data = $request->all();
        $questions = $this->moneyObj->update_account_template($data, auth()->user()->id);
        return redirect()->route('allMoneyAccount')->with('success', $questions);
    }

    /**
     * Remove the specified category from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy_category($id, $dateTime)
    {
        $temp = $this->moneyObj->deleteCategoryTemplate($id, $dateTime);
        return redirect()->route('allMoneyCategory')
                        ->with('success','Money category invactivated successfully');
    }

    /**
     * Remove the specified account from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy_account($id, $dateTime)
    {
        $temp = $this->moneyObj->deleteAccountTemplate($id, $dateTime);
        return redirect()->route('allMoneyAccount')
                        ->with('success','Money account deactivated successfully');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Active the specified category from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    
    public function active_account($id)
    {
        $temp = $this->moneyObj->activeAccountTemplate($id);
        return redirect()->route('deactivateMoneyAccounts')
                        ->with('success','Money account activated successfully');
    }

    /**
     * Active the specified category from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function active_category($id)
    {
        $temp = $this->moneyObj->activeCategoryTemplate($id);
        return redirect()->route('deactivateMoneyCategorys')
                        ->with('success','Money category activated successfully');
    }

    /**
     * Check if money category template exist in storage
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uniqueMoneyCategoryTitle(Request $request)
    {
        $result = $this->moneyObj->uniqueMoneyCategoryTitle($request); 
        return $result;
    }

    /**
     * Check if money account template exist in storage
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uniqueMoneyAccountTitle(Request $request)
    {
        $result = $this->moneyObj->uniqueMoneyAccountTitle($request); 
        return $result;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function deactivateMoneyAccounts()
    {
        $moneyAccountList = $this->moneyObj->deactivateMoneyAccounts();
        //dd($moneyAccountList);
        return view('money.deactivatedMoneyAccounts', ['moneyAccountList' => $moneyAccountList]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function deactivateMoneyCategorys()
    {
        $moneyCategoryList = $this->moneyObj->deactivateMoneyCategorys();
        //dd($moneyAccountList);
        return view('money.deactivateMoneyCategorys', ['moneyCategoryList' => $moneyCategoryList]);
    }
}

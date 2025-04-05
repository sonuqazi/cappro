<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use App\Services\EvaluationService;
use App\Services\UserProfileService;
use App\Services\UserService;

use App\Traits\ProjectTrait;

class EvaluationController extends Controller
{
    use ProjectTrait;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage Client Review Templates', ['only' => ['index','addQuestion','search_all_templates','get_all_questions','destroy','active']]);
        $this->middleware('permission:Manage Time Categories', ['only' => ['time_account','add_time_account','search_all_accounts','get_all_time_accounts','delete_time','active_time','deactivatedCategories', 'deactivatedTimeSpend']]);
        $this->userProfileObj = new UserProfileService(); // user profile Service object
        $this->evalObj = new EvaluationService(); // user profile Service object
        
        
        //$this->uploadFileObj = new UploadFilesService(); // user profile Service object
    }

    /**
     *  Client evaluation listing
     *  @param
     *  @return \Illuminate\Contracts\Support\Renderable
     */

    public function index(){
        $user_id = auth()->user()->id;
        $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
        return view('evaluations.clients', compact('user_profile_data', 'user_id'));
    }

    /**
     * Add template
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function addQuestion(Request $request){
        $data = $request->all();
        $questions = $this->evalObj->update_template($data, auth()->user()->id);
        return redirect()->route('clientEvaluation')->with('success', $questions);
    }

    /**
     * Search templates
     * @param \Illuminate\Http\Request  $request
     * @return json
     */
    public function search_all_templates(Request $request)
    {

        $user_id = auth()->user()->id;
        if ($request->ajax() && !empty($user_id)) {
            $view_templates = $this->evalObj->alltemplates($request);
            $return['recordsFiltered'] = $return['recordsTotal'] = count($view_templates);
            $return['draw'] = $request->draw;
            $options = '';
            $editOption = '';

            //dd($view_templates);
            foreach ($view_templates as $key => $temp) {
                $editOption = '<a class="edit-template del-active" edit-id = "' . $temp->id . '" >Edit</a> ';
                if($temp->is_active == 1){ $del = '<span class="mx-1">|</span> <a class="delete-template del-active" del-id = "'. $temp->id .'" >Deactivate</a>';}else{$del = '<span class="mx-1">|</span> <a class="active-template del-active" act-id = "'. $temp->id .'" >Activate</a>';}
                if(Auth::user()->role_id == 1){
                    $options = $editOption.$del;
                }
                if($temp->created_by == Auth::user()->university_users->id){
                    $options = $editOption.$del;
                }
                $data[$key]['name'] = '<span class="edit-temp-name-' . $temp->id . '">' . $temp->name . '</span>';
                $data[$key]['description'] = '<span class="edit-temp-description-' . $temp->id . '">' . $temp->description . '</span> ';
                $data[$key]['no_ques'] = '<span class="edit-total-ques-' . $temp->id . '">' . $temp->no_ques  . '</span>';
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
    public function get_all_questions(Request $request){
        if ($request->ajax()) {
            $user_student_data = $this->evalObj->get_all_questions_with_template($request);
        }
        return response()->json($user_student_data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $temp = $this->evalObj->deleteTemplate($id);
        return redirect()->route('clientEvaluation')
                        ->with('success','Template deactiveted successfully');
    }

    /**
     * List time account
     * 
     * @param
     * @return \Illuminate\Contracts\Support\Renderable
     */

     public function time_account(){
        $user_id = auth()->user()->id;
        $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
        return view('evaluations.time', compact('user_profile_data', 'user_id'));
     }

     /**
     * Add time account
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function add_time_account(Request $request){
        $data = $request->all();
        $questions = $this->evalObj->update_time($data, auth()->user()->id);
        return redirect()->route('timeEvaluation')->with('success', $questions);

    }

    /**
     * Search accounts
     * @param \Illuminate\Http\Request  $request
     * @return json
     */
    public function search_all_accounts(Request $request)
    {
        $user_id = auth()->user()->id;
        if ($request->ajax() && !empty($user_id)) {
            $view_templates = $this->evalObj->allTimeAccounts($request);
            $return['recordsFiltered'] = $return['recordsTotal'] = count($view_templates);
            $return['draw'] = $request->draw;
            $options = '';
            $editOption = '';
            foreach ($view_templates as $key => $temp) {
                
                $editOption = '<a class="edit-time-account del-active" edit-id = "' . $temp->id . '" >Edit</a> ';
                if($temp->is_active == 1){ $del = '<span class="mx-1">|</span><a class="delete-time-category del-active" del-id = "'. $temp->id .'" >Deactivate</a>';}else{$del = '<span class="mx-1">|</span><a class="active-time-category del-active" act-id = "'. $temp->id .'" >Activate</s>';}
                if(Auth::user()->role_id == 1){
                    $options = $editOption.$del;
                }
                if($temp->created_by == Auth::user()->university_users->id){
                    $options = $editOption.$del;
                }
                $data[$key]['title'] = '<span class="edit-cat-name-' . $temp->id . '">' . $temp->title . '</span>';
                $data[$key]['description'] = '<span class="edit-cat-description-' . $temp->id . '">' . $temp->description . '</span> ';
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
     * @desc Function fetch all time categories in time account
     * @param \Illuminate\Http\Request  $request
     * @return Json
     */
    public function get_all_time_accounts(Request $request){
        if ($request->ajax()) {
            $user_student_data = $this->evalObj->get_all_categories_with_time($request);
        }
        return response()->json($user_student_data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete_time($id, $dateTime)
    {
        $temp = $this->evalObj->deleteTime($id, $dateTime);
        return redirect()->route('timeEvaluation')
                        ->with('success','Time category deactivated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function active($id)
    {
        $temp = $this->evalObj->activeTemplate($id);
        return redirect()->route('clientEvaluation')
                        ->with('success','Template activeted successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function activeFeedback($id)
    {
        $temp = $this->evalObj->activeTemplate($id);
        return redirect()->route('deactivatedFeedbacks')
                        ->with('success','Template activeted successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function active_time($id, $dateTime)
    {
        $temp = $this->evalObj->activeTime($id, $dateTime);
        return redirect()->route('deactivatedTimeSpend')
                        ->with('success','Time category activated successfully');
    }

    /**
     * Check client review question exist in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function isClientReviewQuestionExist(Request $request)
    {
        $count = $this->isClientReviewQuestionExists($request->clientReviewQuestionId);
        if($count > 0){
            return $count;
        }else{
            return $count;
        }
    }

    /**
     * Check time category exist in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uniqueTimeCategoryTitle(Request $request)
    {
        $result = $this->evalObj->uniqueTimeCategoryTitle($request);
        return $result;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function deactivatedFeedbacks()
    {
        $feedbackList = $this->evalObj->deactivatedFeedbacks();
        //dd($feedbackList);
        return view('evaluations.deactivatedFeedbacks', ['feedbackList' => $feedbackList]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function deactivatedTimeSpend()
    {
        $timeSpend = $this->evalObj->deactivatedTimeSpend();
        //dd($timeSpend);
        return view('evaluations.deactivatedTimeSpend', ['timeSpend' => $timeSpend]);
    }
}

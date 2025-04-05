<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use App\Services\PeerEvaluationService;
use App\Services\UserProfileService;
use App\Services\UserService;

class PeerEvaluationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage Peer Evaluation Templates', ['only' => ['index', 'add_time_account','search_all_accounts','get_all_time_accounts','delete_time','active']]);
        $this->userProfileObj = new UserProfileService(); // user profile Service object
        $this->peerEvalObj = new PeerEvaluationService(); // user profile Service object

        //$this->uploadFileObj = new UploadFilesService(); // user profile Service object
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user_id = auth()->user()->id;
        $user_profile_data = $this->userProfileObj->getUserProfile($user_id);
        return view('peer_evaluations.index', compact('user_profile_data', 'user_id'));
    }

    /**
     * Add template
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function addRating(Request $request){
        $data = $request->all();
        $questions = $this->peerEvalObj->update_template($data, auth()->user()->id);
        return redirect()->route('allPeerEvaluation')->with('success', $questions);
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
            $view_templates = $this->peerEvalObj->alltemplates($request);
            $return['recordsFiltered'] = $return['recordsTotal'] = count($view_templates);
            $return['draw'] = $request->draw;
            $options = '';
            $editOption = '';

            foreach ($view_templates as $key => $temp) {
                $editOption = '<a class="edit-template del-active" edit-id = "' . $temp->id . '" >Edit</a> ';
                if($temp->is_active == 1){ $del = '<span class="mx-1">|</span> <a class="delete-peer del-active" del-id = "'. $temp->id .'" >Deactivate</a>';}else{$del = '<span class="mx-1">|</span> <a class="active-peer del-active" act-id = "'. $temp->id .'" >Activate</a>';}
                if(Auth::user()->role_id == 1){
                    $options = $editOption.$del;
                }
                if($temp->created_by == Auth::user()->university_users->id){
                    $options = $editOption.$del;
                }
                $data[$key]['name'] = '<span class="edit-temp-name-' . $temp->id . '">' . $temp->name . '</span>';
                $data[$key]['description'] = '<span class="edit-temp-description-' . $temp->id . '">' . $temp->description . '</span> ';
                $data[$key]['no_rating'] = '<span class="edit-total-rating-' . $temp->id . '">' . $temp->no_rating  . '</span>';
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
    public function get_all_ratings(Request $request){
        if ($request->ajax()) {
            $user_student_data = $this->peerEvalObj->get_all_ratings_with_template($request);
        }
        return response()->json($user_student_data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, $dateTime)
    {
        $temp = $this->peerEvalObj->deleteTemplate($id, $dateTime);
        return redirect()->route('allPeerEvaluation')
                        ->with('success','Peer evaluation template deactivated successfully');
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
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function add_time_account(Request $request){
       
        $data = $request->all();
        $questions = $this->peerEvalObj->update_time($data, auth()->user()->id);
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
            $return['recordsFiltered'] = $return['recordsTotal'] = $this->peerEvalObj->search_all_time_account_counts($request);
            $return['draw'] = $request->draw;
            $view_templates = $this->peerEvalObj->allTimeAccounts($request);
            foreach ($view_templates as $key => $temp) {
                 
                if($temp->is_assigned == 0){ $del = '<span class="mx-1">|</span><a class="delete-time-category del-active" del-id = "'. $temp->id .'" >Delete</a>';}else{$del = '';}
                $data[$key]['title'] = '<span class="edit-cat-name-' . $temp->id . '">' . $temp->title . '</span>';
                $data[$key]['description'] = '<span class="edit-cat-description-' . $temp->id . '">' . $temp->description . '</span> ';
                $data[$key]['total_ques'] = '<span class="edit-total-ques-' . $temp->id . '">' . $temp->no_cat  . '</span>';
                $data[$key]['edit'] = '<a class="edit-time-account del-active" edit-id = "' . $temp->id . '" >Edit</a> '.$del; 
                $data[$key]['id'] = $temp->id;
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
            $user_student_data = $this->peerEvalObj->get_all_categories_with_time($request);
        }
        return response()->json($user_student_data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete_time($id)
    {
        $temp = $this->peerEvalObj->deleteTime($id);
        return redirect()->route('timeEvaluation')
                        ->with('success','Time category deleted successfully');
    }

    /**
     * Active the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function active($id)
    {
        $temp = $this->peerEvalObj->activeTemplate($id);
        return redirect()->route('deactivatedPeerEvaluations')
                        ->with('success','Peer evaluation template activated successfully');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function deactivatedPeerEvaluations()
    {
        $peerEvalList = $this->peerEvalObj->deactivatedPeerEvaluations();
        //dd($peerEvalList);
        return view('peer_evaluations.deactivatedPeerEvaluations', ['peerEvalList' => $peerEvalList]);
    }
}
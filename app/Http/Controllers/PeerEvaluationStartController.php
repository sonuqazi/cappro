<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;

use App\Services\PeerEvaluationService;
use App\Services\UserProfileService;
use App\Services\UserService;
use App\Services\ProjectService;

use App\Traits\ProjectTrait;

use View;

class PeerEvaluationStartController extends Controller
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
        $this->middleware(function ($request, $next) {
            $this->user = Auth::user();
                    if( $this->user->role_id==Config::get('constants.roleTypes.admin') ||
                        $this->user->role_id==Config::get('constants.roleTypes.faculty') ||
                        $this->user->role_id==Config::get('constants.roleTypes.ta'))
                    {
                        $this->middleware('role:Admin|Faculty|TA',['only' => ['store','startRating','ratedEvaluation','isPeerEvalCriteriaExist']]);
                    }
                    if($this->user->role_id==Config::get('constants.roleTypes.student'))
                    {
                        $this->middleware('role:Student',['only' => ['rateNow','startRating']]);
                    }
                 return $next($request);
                });
       
       
        $this->peerEvalObj = new PeerEvaluationService(); // peer evaluation Service object
        $this->userProfileObj = new UserProfileService(); // user profile Service object
        $this->userObj = new UserService(); // user Service object
        $this->projectObj = new ProjectService(); // Project Service object
        //$this->uploadFileObj = new UploadFilesService(); // user profile Service object
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
        try {
            $data = $request->all();
            $result = $this->peerEvalObj->startEvaluationRatting($data);
            //dd($result);
            if($result){
                $res=$this->userObj->peerEvaluationMailNotification($data['project_course_id']);
                return redirect()->back()->with('success','Peer evaluation started successfully.');
            }else{
                return redirect()->back()->with('error','There is no team for selected project.');
            }
            
        } catch (Throwable $e) {
            report($e);
            return redirect()->back()->with('error', $e);
        }
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
    public function edit(Request $request)
    {
        $result = $this->peerEvalObj->getEvalData($request->project_course_id);
        return response()->json($result);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $check = $this->peerEvalObj->isEvaluationDone($request->project_course_id);
        if($check){
            return redirect()->back()->with('error', 'Peer Evaluation already submited. You can not make change.');
        }else{
            $this->peerEvalObj->updatePeerEvaluation($request);
            return redirect()->back()->with('success', 'Peer evaluation updated successfully.');
        }
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
     * Retrive specified resource from storage.
     *
     * @param 
     * @return \Illuminate\Http\Response
     */
    public function startRating($semId=null)
    {
        try {
            $user = Auth::user();
            $user_profile_data = $this->userProfileObj->getUserProfile($user->id);
            $universityUserId = $this->userProfileObj->getUserUniversityID($user->id);
            
            $teamData['active'] = $this->peerEvalObj->getUserTeam($universityUserId,1,$semId);
            //$teamData['archive'] = $this->peerEvalObj->userTeam($universityUserId,3);
            $allSemesters = $this->projectObj->getAllSemesters();
            return view('peer_evaluations.start_ratting', ['requests' => $teamData, 'user_profile_data' => $user_profile_data, 'semesters' => $allSemesters, 'semId' => $semId]);
            
        } catch (Throwable $e) {
            report($e);
            return redirect()->back()->with('error', $e);
        }
    }

    /**
     * Peer evaluation star rating to student
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function rateNow(Request $request)
    {
        try {
            $user = Auth::user();
            $universityUserId = $this->userProfileObj->getUserUniversityID($user->id);
            foreach($request['peer_evaluation_ratting_id'] as $key => $data ){
                $dataArray[$key]['peer_evaluation_ratting_id']=$data;
                //$dataArray[$key]['rate_to']='rate_to'[$key];
                foreach($request['rate_to'] as $key1 => $rateTo){
                    $dataArray[$key1]['rate_to']=$rateTo;
                }
                foreach($request['rate'] as $key2 => $rate){
                    $dataArray[$key2]['rate']=$rate;
                }
                $dataArray[$key]['peer_evaluation_id']=$request['peer_evaluation_id'];
                $dataArray[$key]['project_course_id']=$request['project_course_id'];
                $dataArray[$key]['project_id']=$request['project_id'];
                $dataArray[$key]['team_id']=$request['team_id'];
                $dataArray[$key]['created_by']=$universityUserId->id;
                $dataArray[$key]['created_at']=$request['updated_at'];
            }
            
            $result = $this->peerEvalObj->rateNow($dataArray);
            if($result){
                return response()->json(array('response' => 'success', 'url' => url('/peer_evaluation/startRating')));
            }else{
                return response()->json(array('response' => 'failed', 'url' => url('/peer_evaluation/startRating')));
            }
        } catch (Throwable $e) {
            report($e);
            return redirect()->back()->with('error', $e);
        }
    }

    /**
     * Get rated peer evaluation rated by users
     * @param 
     * @return \Illuminate\Http\Response
     */
    public function ratedEvaluation($semId=null)
    {
        $user = Auth::user();
        $roleId = auth()->user()->role_id;
        $user_profile_data = $this->userProfileObj->getUserProfile($user->id);
        $universityUserId = $this->userProfileObj->getUserUniversityID($user->id);
        
        $evalData['active'] = $this->peerEvalObj->getRatedPeerEvaluation($universityUserId,1,$roleId, $semId);
        //$evalData['archive'] = $this->peerEvalObj->getRatedPeerEvaluation($universityUserId,3,$roleId);
        $allSemesters = $this->projectObj->getAllSemesters();
        // foreach($evalData['active'][64]['ratting']['98']['rated'] as $dd){
        //     dump($dd);
        // }die;
        //dd($evalData['active']);
        return view('peer_evaluations.rated_evaluation', ['requests' => $evalData, 'user_profile_data' => $user_profile_data, 'semesters' => $allSemesters, 'semId' => $semId]);
         
    }

    /**
     * Check peer evaluation criteria exist in storage.
     *
     * @param \Illuminate\Http\Request  $request
     * @return boolean
     */
    public function isPeerEvalCriteriaExist(Request $request)
    {
        $count = $this->isPeerEvalCriteriaExists($request->criteriaId);
        if($count > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Semd peer evaluation reminder email.
     *
     * @param \Illuminate\Http\Request  $request
     * @return JSON
     */
    public function sendReminderMail(Request $request)
    {
        $evalData = $this->peerEvalObj->getStudentSendReminder($request->projectCourseId);
        foreach($evalData as $key => $data){
            foreach($data as $key1 => $email){
            $check = $this->peerEvalObj->checkPeerEvaluation($request->projectCourseId, $email['id'], $email['team_id']);
            if($check){
                $mailSubject="Complete Peer Evaluation";
                $content = "This is an automated message. Please login and complete the peer evaluation.";
                $view = View::make('email/adminProject', ['admin_name' => $email['name'], 'content' => $content]);
                $mailMsg = $view->render();
                send_mail($email['email'], $mailSubject, $mailMsg);
            }
            }
        }
        return response()->json(['status' => '200', 'response' => 'success']);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

use Illuminate\Http\Request;
use App\Services\EvaluationStartService;
use App\Services\UserProfileService;
use App\Services\ProjectService;
use View;
class EvaluationStartController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        // $this->middleware('permission:Manage Client Review Templates', ['only' => ['index','addQuestion','search_all_templates','get_all_questions','destroy','active']]);
        // $this->middleware('permission:Manage Time Categories', ['only' => ['time_account','add_time_account','search_all_accounts','get_all_time_accounts','delete_time','active_time','deactivatedCategories']]);
       
        $this->middleware(function ($request, $next) {
        $this->user = Auth::user();
                if( $this->user->role_id==Config::get('constants.roleTypes.admin') ||
                    $this->user->role_id==Config::get('constants.roleTypes.faculty') ||
                    $this->user->role_id==Config::get('constants.roleTypes.ta'))
                {
                    $this->middleware('role:Admin|Faculty|TA',['only' => ['store','startEvaluatinRating','clientReviewedEvaluation']]);
                }
                if($this->user->role_id==Config::get('constants.roleTypes.client'))
                {
                    $this->middleware('role:Client',['only' => ['startEvaluatinRating','evaluateNow','clientReviewedEvaluation']]);
                }
             return $next($request);
            });
        
        
        $this->evalStartObj = new EvaluationStartService(); // user profile Service object
        $this->userProfileObj = new UserProfileService(); // user profile Service object
        $this->projectObj = new ProjectService(); // Project Service object
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
        $clientData = $this->userProfileObj->getUserProfileByUniversityUser($request->client_id);
            $result = $this->evalStartObj->insertEvalStart($request);
            if ($result->exists){
                $mailTo = $clientData->university_users->email;
                $mailSubject = 'Complete Project Evaluation';
                $content = 'This is an automated message. Please login to CapstonePro and complete the evaluation survey. Your time and effort are appreciated.';
                $link = url('/evaluation/startEvaluationRating?id='.$result->project_course_id);
                $view = View::make('email/clientReview', ['client_name' => $clientData->university_users->first_name . ' ' . $clientData->university_users->last_name, 'content' => $content, 'link' => $link]);
                $mailMsg = $view->render();
                
                send_mail($mailTo, $mailSubject, $mailMsg);
                return redirect()->back()->with('success', 'Client evaluation started successfully.');
            }else{
                return redirect()->back()->with('error', 'Client evaluation started failed.');
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
    public function update(Request $request)
    {
        $check = $this->evalStartObj->isFeedbackDone($request->project_course_id);
        if($check){
            return redirect()->back()->with('error', 'Client feedback already submited. You can not make change.');
        }else{
            $this->evalStartObj->updateFeedback($request);
            return redirect()->back()->with('success', 'Client feedback updated successfully.');
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
     * Evaluation review start
     * @return \Illuminate\Http\Response
     */
    public function startEvaluatinRating($semId=null)
    {
        $evalData = $this->evalStartObj->getEvaluationStart($semId);
        $allSemesters = $this->projectObj->getAllSemesters();
        return view('evaluations.start_evaluation_ratting', ['requests' => $evalData, 'semesters' => $allSemesters, 'semId' => $semId]);
    }

    /**
     * Evaluation star rating to evaluation
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function evaluateNow(Request $request)
    {
        if ($request->ajax()) {
        $user = Auth::user();
        $universityUserId = $this->userProfileObj->getUserUniversityID($user->id);
        foreach($request['evaluation_question_id'] as $key => $data ){
            $dataArray[$key]['evaluation_question_id']=$data;
            foreach($request['rate'] as $key2 => $rate){
                $dataArray[$key2]['rate']=$rate;
            }
            $dataArray[$key]['evaluation_id']=$request['evaluation_id'];
            $dataArray[$key]['project_course_id']=$request['project_course_id'];
            $dataArray[$key]['created_by']=$universityUserId->id;
            $dataArray[$key]['created_at']=$request['updated_at'];
        }
        $result = $this->evalStartObj->evaluateNow($dataArray);
        if($result){
            return response()->json(array('response' => 'success', 'url' => url('/evaluation/startEvaluationRating')));
        }else{
            return response()->json(array('response' => 'failed', 'url' => url('/evaluation/startEvaluationRating')));
        }
        }
    }

    /**
     * Get reviewed evaluation reviewed by client
     * @return \Illuminate\Http\Response
     */
    public function clientReviewedEvaluation($semId = null)
    {
        //dump($semId);
        $semester = $this->projectObj->getCurrentSemester();
        $semester_id = $semester->id;
        //dd($semester_id);
        $evalData = $this->evalStartObj->clientReviewedEvaluation(Auth::user()->university_users->id,5,Auth::user()->role_id, $semId);
        $allSemesters = $this->projectObj->getAllSemesters();
        //dd($allSemesters);
        return view('evaluations.reviewed_evaluation', ['requests' => $evalData, 'semesters' => $allSemesters, 'semester_id' => $semester_id, 'semId' => $semId]);
         
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getFeedbackData(Request $request)
    {
        $result = $this->evalStartObj->getFeedbackData($request->project_course_id);
        return response()->json($result);
    }
}

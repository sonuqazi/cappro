<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\Evaluation;
use App\Models\EvaluationStart;
use App\Models\EvaluationQuestionStar;
use App\Models\EvaluationQuestion;

use DB;


class EvaluationStartService
{
    /**
     * @description get the evaluations 
     * @return oject $result
     */
    public function evaluationList()
    {
        $result = Evaluation::select('id', 'name', 'description', 'is_active', 'is_deleted', 'is_assigned')->where('is_active', 1)->get();
        return $result;
    }

    /**
     * @description insert the evaluation start 
     * @param object $request
     * @param int $userId 
     */
    public function insertEvalStart($request)
    {
        $data = array(
            'evaluation_id' => $request->evaluation_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'project_course_id' => $request->project_course_id,
            'client_id' => $request->client_id,
            'created_by' => Auth::user()->university_users->id,
            'created_at' => $request->updated_at,
            'updated_by' => Auth::user()->university_users->id
        );
        return EvaluationStart::create($data);
    }

     /**
     * @description evaluation start 
     * @param Array $records 
     */
    public function getEvaluationStart($semId=null)
    {
        $records =[];
        $query = EvaluationStart::select('evaluation_starts.*')->with(['started_by.university_users', 'evaluations.evaluation_question', 'project_courses.projects', 'project_courses'=>function($q){
            $q->with(['courses'=>function($q){
                $q->with('semesters')->get();
            },])->get();
        }])
        ->join('project_courses', 'project_courses.id', '=', 'evaluation_starts.project_course_id')
        ->join('projects', 'projects.id', '=', 'project_courses.project_id')
        ->join('courses', 'courses.id', '=', 'project_courses.course_id')
        ->where('evaluation_starts.client_id', Auth::user()->university_users->id);
        if($semId){
            $query->where('courses.semester_id', $semId);
        }
        $evalArray = $query->get();
        foreach($evalArray as $key => $evalData){
            $records[$key] = $evalData;
            $records[$key]['questions'] = $this->getEvaluationQuestions($evalData->evaluation_id);
            $records[$key]['rated'] = $this->checkIfRated($evalData->project_course_id, Auth::user()->university_users->id);
        }
        
        return $records;
    }

    /**
     * @description Evalution Questins
     * @param int $evalId
     * @return object $result
     */
    public function getEvaluationQuestions($evalId = null)
    {
        $result = EvaluationQuestion::where('evaluation_questions.evaluation_id', $evalId)->get();
        return $result;
    }


    /**
     * @description Check if rated
     * @param int $projectCourseId
     * @return object $result
     */
    public function checkIfRated($projectCourseId = null)
    {
        $result = EvaluationQuestionStar::where('evaluation_question_stars.project_course_id', $projectCourseId)
        ->where('evaluation_question_stars.created_by', Auth::user()->university_users->id)->get();
        return $result;
    }

    /** 
     * evaluate the project
     * @param $request form data
     * @return boolean
     */
    public function evaluateNow($data)
    {
        $result =   EvaluationQuestionStar::insert($data);
        return $result;
    }

    /** 
     * Get rated peer evaluation
     * @param $universityUserId, $status
     * @return $ratedEvaluation
     */
    public function clientReviewedEvaluation($universityUserId = null, $status, $roleId=null, $semId=null)
    {        
        //dd($semId);
        $evaluation=$temp=[];
        if($roleId!=1){
            $query = EvaluationStart::with(['evaluations', 'project_courses.projects', 'project_courses'=>function($q){
                $q->with(['courses'=>function($q){
                    $q->with('semesters')->get();
                },])->get();
            }])
            ->join('project_courses', 'project_courses.id', '=', 'evaluation_starts.project_course_id')
            ->join('projects', 'projects.id', '=', 'project_courses.project_id')
            ->join('courses', 'courses.id', '=', 'project_courses.course_id')
            //->where('evaluation_starts.created_by', $universityUserId)
            ->where('projects.status', $status);
            if(Auth::user()->role_id == 5){
                $query->where('courses.ta_id', '=', Auth::user()->university_users->id);
            }else{
                $query->where('courses.faculty_id', '=', Auth::user()->university_users->id);
            }
            if($semId){
                $query->where('courses.semester_id', $semId);
            }
            $temp = $query->get();
        }else{
            $query = EvaluationStart::with(['evaluations', 'project_courses.projects', 'project_courses'=>function($q){
                $q->with(['courses'=>function($q){
                    $q->with('semesters')->get();
                },])->get();
            }])
            ->join('project_courses', 'project_courses.id', '=', 'evaluation_starts.project_course_id')
            ->join('projects', 'projects.id', '=', 'project_courses.project_id')
            ->join('courses', 'courses.id', '=', 'project_courses.course_id')
            ->where('projects.status', $status);
            if($semId){
                $query->where('courses.semester_id', $semId);
            }
            $temp = $query->get();
        }
        //dd($temp);
        if(count($temp)>0){
            $i=0;
            foreach($temp as $key => $eval){
                $evaluation[$key] = $eval;
                $evaluation[$key]['questions'] = $this->getEvaluationQuestions($eval->evaluations->id);

                $evaluation[$key]['rated'] = $this->ratedUsers($eval->project_course_id, $eval->client_id);
                //$evaluation[$key]['average'] = $this->ratingAverage($eval['project_course_id'], $eval['team_id']);
                $i++;
            }
        }else{
            $evaluation=[];
        }
        //dd($evaluation);
        return $evaluation;
    }
    /**
     * @description rated users
     * @param int $projectCourseId, date $createdBy
     * @return object $result
     */
    public function ratedUsers($projectCourseId = null, $createdBy = null)
    {
        $result[] = EvaluationQuestionStar::with(['created_by.university_users'])->where('evaluation_question_stars.project_course_id', $projectCourseId)
            ->where('evaluation_question_stars.created_by', $createdBy)->orderBy('created_at', 'asc')->get();
        return $result;
    }

   /**
     * @description Rating Average
     * @param int $projectCourseId
     * @return boolean
     */
    public function ratingAverage($projectCourseId = null)
    {
        $result = EvaluationQuestionStar::where('evaluation_question_stars.project_course_id', $projectCourseId)
            ->sum('rate');
        
        $total = EvaluationQuestionStar::where('evaluation_question_stars.project_course_id', $projectCourseId)
            ->select('rate')->get();
        if(count($total) > 0){
            return $result/count($total);
        }else{
            return false;
        }
    }

    /**
     * @Get Evaluation Details
     * @param int $ProjectCourseId
     * @return object
     */
    public function getFeedbackData($ProjectCourseId = null)
    {
        $result = EvaluationStart::where('evaluation_starts.project_course_id', $ProjectCourseId)->first();
        return $result;
    }

    /** 
     * Update Evaluation Data
     * @param object $request
     * @return boolean
     */
    public  function updateFeedback($request)
    {
        $start = explode('-', $request->start_date);
        $startDate = $start[2].'-'.$start[0].'-'.$start[1];
        $end = explode('-', $request->end_date);
        $endDate = $end[2].'-'.$end[0].'-'.$end[1];
        
        $data['evaluation_id']=$request->evaluation_id;
        $data['start_date']=$startDate;
        $data['end_date']=$endDate;
        $data['project_course_id']=$request->project_course_id;
        $data['updated_by']=$request->updated_by;
        $data['updated_at']=$request->updated_at;
        
        EvaluationStart::where('id', $request->id)->update($data);
        return true;
    }

    /**
     * @Check if feedback is already done by client
     * @param int $ProjectCourseId
     * @return boolean
     */
    public function isFeedbackDone($projectCourseId)
    {
        $count = EvaluationQuestionStar::where('evaluation_question_stars.project_course_id', $projectCourseId)
        ->select('*')->count();
        if($count > 0){
            return true;
        }else{
            return false;
        }
    }
}

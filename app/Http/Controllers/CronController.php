<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use App\Services\ProjectService;
use Validator;
use DB;
use View;

class CronController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->projectObj = new ProjectService();
    }

    /**
     * 7 day's proyer sent mail to students 
     * @return $result
     */
    public function sentMailToStudents()
    {      
        //if(in_array($_SERVER['REMOTE_ADDR'], Config::get('constants.ipAddresses'))){
            $results = $this->projectObj->getStudents();
            if($results){
                foreach($results as $key => $students){
                    foreach($students->teams->team_students_details as $key1 => $studentDetails){
                        $ifEnable = $this->projectObj->checkMilestoneEmailNotificationEnable($studentDetails->user_id);
                        if($ifEnable){
                            if($ifEnable->status == 1){
                                $mailData[] = $studentDetails->email;
                                $mailTo = $studentDetails->email;
                                $mailSubject = 'Milestone Deadline Coming Soon';
                                $content = 'This is an automated message. Your project milestone is due on ';
                                $content1 = getProjectFullName($students->teams->project_course_id);
                                $content2 = $students->teams->name;
                                $content3 = $students->milestones->project_plan[0]->name;
                                $content4 = $students->milestones->name;
                                $endDate = date("F d, Y", strtotime($students->end_date));
                                
                                $link = url('/milestone/index/'.$students->teams->project_course_id.'/'.$students->team_id);
                                $view = View::make('email/milestoneDeadlineMail', ['student_name' => $studentDetails->first_name . ' ' . $studentDetails->last_name, 'content' => $content, 'link' => $link, 'content1' => $content1, 'content2' => $content2, 'content3' => $content3, 'content4' => $content4, 'endDate' => $endDate]);
                                $mailMsg = $view->render();
                                
                                send_mail($mailTo, $mailSubject, $mailMsg);
                            }
                        }
                    }
                }
            }
        //}
    }
}

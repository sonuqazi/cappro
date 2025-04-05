<?php

namespace App\Services;

use App\Models\ProjectRequest;
use App\Models\ProjectCourseSetting;
use App\Models\TeamStudent;
use App\Models\Message;
use App\Models\EvaluationStart;
use App\Models\EvaluationQuestionStar;
use App\Models\User;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use DB;


class MessageService
{
    /**
     * Unread messages
     * @param $sender_id|$receiver_id
     * @return $unreadMsg
     */
    public function message_count($sender_id = null, $receiver_id = null)
    {
        $count =  DB::table('messages')->select('*')
            ->where('messages.sender_id', $sender_id)
            ->where('messages.receiver_id', $receiver_id)
            ->Where('messages.is_read', '0')
            ->orderBy('messages.id', 'desc')->get();
            
        $unreadMsg = count($count);
        if($unreadMsg > 0){
            return $unreadMsg;
        }else{
            return '';
        }
        
    
    }

    /**
     * Last messages date
     * @param $sender_id|$receiver_id
     * @return $date
     */
    public function lastMessageDate($sender_id = null, $receiver_id = null)
    {
        $date =  DB::table('messages')->select('*')
            ->where('messages.sender_id', $sender_id)
            ->where('messages.receiver_id', $receiver_id)->orderBy('sent_at', 'desc')->first();
        
        if(!empty($date->sent_at)){
            return $date->sent_at;
        }else{
            return '';
        }
        
    
    }

    /**
     * Individual unread messages count
     * @param $user_id
     * @return $unreadMsg
     */
    public function totalIndividualMessageCount($user_id = null)
    {
        $count =  DB::table('messages')->select('*')
            ->where('messages.receiver_id', $user_id)
            ->Where('messages.is_read', '0')
            ->Where('messages.is_all_read', '0')->get();
            
        $unreadMsg = count($count);
        if($unreadMsg > 0){
            return $unreadMsg;
        }else{
            return '';
        }
        
    
    }

    /**
     * Unread messages count
     * @param $user_id
     * @return $unreadMsg
     */
    public function totalUnreadMessageCount($user_id = null)
    {
        $count =  DB::table('messages')->select('*')
            ->where('messages.receiver_id', $user_id)
            ->Where('messages.is_all_read', '0')
            ->orderBy('messages.id', 'desc')->get();
            
        $unreadMsg = count($count);
        if($unreadMsg > 0){
            return $unreadMsg;
        }else{
            return '';
        }
        
    
    }

    /**
     * Count unread project request
     * @param 
     * @return $unseenRequests
     */
    public function totalUnreadRequestCount()
    {
        $requestCount = ProjectRequest::where('is_seen', 0)->get();
        $unseenRequests = count($requestCount);
        if($unseenRequests > 0){
            return $unseenRequests;
        }else{
            return '';
        }
    }

    /**
     * Can chat with other team members
     * @param $projectCourseId|$teamId
     * @return $result
     */
    public function talkToOtherTeamMembers($projectCourseId = null, $teamId = null)
    {
        $result = ProjectCourseSetting::where('project_course_id', $projectCourseId)
        ->where('communication_type', 1)->first();

        //Check student in team
        $isInTeam = TeamStudent::where('team_id', $teamId)
            ->where('student_id', Auth::user()->university_users->id)
            ->where('is_deleted', Config::get('constants.is_deleted.false'))->first();
        if($result){            
            if($result->status == 1){
                return 1;
            }else{
                if($isInTeam){
                    return 1;
                }else{
                    return 0;
                }
            }
        }else{
            if($isInTeam){
                return 1;
            }else{
                return 0;
            }
        }
    }

    /**
     * can chat with client and instructor
     * @param $projectCourseId|$status
     * @return $result
     */
    public function talkToClientOrInstructors($projectCourseId = null, $status)
    {
        $result = ProjectCourseSetting::where('project_course_id', $projectCourseId)
        ->where('communication_type', $status)->first();
        if($result){            
            if($result->status == 1){
                return 1;
            }else{
                return 0;
            }
        }else{
            return 0;
        }
    }

    /**
     * Check if message is coming from Faculty, Ta and Client
     * @param $senderId
     * @return $result
     */
    public function checkMessage($senderId = null, $projectCourseId = null)
    {
        $result = Message::where('sender_id', $senderId)
        ->where('receiver_id', Auth::user()->university_users->id)
        ->where('project_course_id', $projectCourseId)->count();
        return $result;
    }

    /**
     * Count review request
     * @param 
     * @return $pendingReviewRequestCount
     */
    public function pendingReviewRequestCount()
    {
        $result = EvaluationStart::where('client_id', Auth::user()->university_users->id)->get();
        $pendingReviewRequestCount = 0;
        foreach($result as $key => $value){
            $data = EvaluationQuestionStar::where('project_course_id', $value->project_course_id)->count();
            if($data == 0){
                $pendingReviewRequestCount++;
            }
        }
        if($pendingReviewRequestCount > 0){
            return $pendingReviewRequestCount;
        }else{
            return '';
        }
    }

    /**
     * Update all message count
     * @param 
     * @return boolean
     */
    public function allMessageCountUpdate()
    {
        Message::where('receiver_id', Auth::user()->university_users->id)->where('is_all_read', '0')->update(['is_all_read' => '1']);
    }

    /**
     * Update unseen message count
     * @param 
     * @return boolean
     */
    public function allUnseenMessageCountUpdate()
    {
        Message::where('receiver_id', Auth::user()->university_users->id)->where('is_read', '0')->update(['is_all_read' => '0']);
    }

    /**
     * get all admins
     * @param 
     * @return array
     */
    public function getAdminList()
    {
        return User::select('first_name', 'last_name', 'email')->where('role_id', 1)->where('is_deleted', '0')->where('main_contact', '1')->get();
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use Validator;
use App\Models\UniversityUser;
use App\Models\UserProfile;
use App\Models\User;

use App\Models\ProjectCourse;
use App\Models\Team;
use App\Models\TeamStudent;
use App\Models\HelpRequest;

use Illuminate\Support\Facades\DB;

use App\Services\UserProfileService;
use App\Services\UserService;
use App\Services\MessageService;
use App\Services\ProjectService;

use View;

class MessageController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth');
        $this->userProfileObj = new UserProfileService(); // user profile Service object
        $this->messageObj = new MessageService(); // user profile Service object
        $this->projectObj = new ProjectService();
        $this->userObj= new UserService();
    }

    /**
     * Display a listing of the resource.
     *
     * @param  int  $project_course_id|$team_id|$userId
     * @return \Illuminate\Http\Response
     */
    public function index($project_course_id = null, $team_id = null, $userId = null)
    {
        $team_arr = [];
        $user =  Auth::user();
        $update = $this->messageObj->allMessageCountUpdate();
        $user_profile_data = $this->userProfileObj->getUserProfileByUniversityUser($user->university_users->id);
        $universityUserId = $user_profile_data->university_users->id;
        //dd($team_id);
        $projects = $this->projectObj->getProjects($user->university_users->id, $user->role_id, 'message');
        //dd($projects);
        $teams_details = (!empty($project_course_id)) ? $this->projectObj->getProjectTeams($project_course_id, $universityUserId, '', $isDeleted=2) : [];
        if (!empty($teams_details)) {
            foreach ($teams_details as $count_team => $team_detail_arr) {
                $team_arr[$team_detail_arr->course_name][] = $team_detail_arr;
            }
        }
        // $users = User::WhereIn('role_id', ['2', '3', '5'])->Where('status', '1')->where('id', '!=', $user->id)->get();
        // dd($users);
        // foreach($users as $user){
        //     $linked_users['id'] = $user['id'];
        //     $linked_users['first_name'] = $user['first_name'];
        //     $linked_users['last_name'] = $user['last_name'];
        //     $linked_users['profile_image'] = $user['profile_image'];
        //     $linked_users['role_name'] = $user['role_name'];
        // }
        if($user->role_id == 1){
            $users_data =  DB::table('users')->select('university_users.id as id', 'users.first_name as first_name', 'users.last_name as last_name', 'users.profile_image as profile_image', 'roles.name as role_name')
            ->join('university_users', 'university_users.user_id', '=', 'users.id')
            ->Join('roles', 'roles.id', '=', 'users.role_id')
            ->leftJoin('messages', 'messages.sender_id', '=', 'users.id')
            ->leftJoin('project_courses', 'project_courses.id', '=', 'messages.project_course_id')
            ->leftJoin('projects', 'projects.id', '=', 'project_courses.project_id')
            ->leftJoin('teams', 'teams.id', '=', 'messages.team_id')
            ->WhereIn('role_id', ['2', '3', '4', '5'])
            ->where('users.id', '!=', $user->id)
            // ->where('messages.receiver_id', auth()->user()->id)
            // ->orwhere('messages.project_course_id', $project_course_id)
            // ->orwhere('messages.team_id', $team_id)
            ->Where('users.status', '1')->Where('users.is_deleted', '0')->get();
            //dd($users_data);
        }else{
            $users_data =  DB::table('users')->select('university_users.id as id', 'users.first_name as first_name', 'users.last_name as last_name', 'users.profile_image as profile_image', 'roles.name as role_name', 'messages.id as msg_id', 'messages.sent_at as msg_date', 'messages.project_course_id', 'messages.team_id')
            ->join('university_users', 'university_users.user_id', '=', 'users.id')
            ->leftJoin('messages', 'messages.sender_id', '=', 'users.id')
            ->leftJoin('project_courses', 'project_courses.id', '=', 'messages.project_course_id')
            ->leftJoin('projects', 'projects.id', '=', 'project_courses.project_id')
            ->leftJoin('teams', 'teams.id', '=', 'messages.team_id')    
            ->Join('roles', 'roles.id', '=', 'users.role_id')
            
            ->WhereIn('role_id', ['2', '3', '4', '5'])
            ->where('users.id', '!=', $user->id)
            ->where('messages.receiver_id', $user->university_users->id)
            ->where('messages.project_course_id', $project_course_id)
            ->where('messages.team_id', $team_id)
            ->Where('projects.status', '2')
            ->Where('users.status', '1')->Where('users.is_deleted', '0')->orderBy('msg_date', 'DESC')->get();
            //dd($users_data);
        }
            $linked_users = [];
        foreach($users_data as $key => $users){
            $linked_users[$users->id]['id'] = $users->id;
            $linked_users[$users->id]['first_name'] = $users->first_name;
            $linked_users[$users->id]['last_name'] = $users->last_name;
            $linked_users[$users->id]['profile_image'] = $users->profile_image;
            $linked_users[$users->id]['role_name'] = $users->role_name;
            if(isset($users->project_course_id)){
                $pcId = $users->project_course_id;
            }else{
                $pcId = 0;
            }
            if(isset($users->team_id)){
                $tId = $users->team_id;
            }else{
                $tId = 0;
            }
            if(isset($users->msg_date)){
                $msgDate = $users->msg_date;
            }else{
                $msgDate = '';
            }
            $linked_users[$users->id]['project_course_id'] = $pcId;
            $linked_users[$users->id]['team_id'] = $tId;
            $msgCount = $this->messageObj->message_count($users->id, $user->university_users->id);
            if($msgCount > 0){
                $unreadMsgCount = $msgCount;
                $newClass = 'msg-bg-color';
            }else{
                $unreadMsgCount = $newClass = '';
            }
            $linked_users[$users->id]['msg_count'] = $unreadMsgCount;
            $linked_users[$users->id]['unreadMsgClass'] = $newClass;
            $linked_users[$users->id]['msg_date'] = $msgDate;

        }
        return view('messages.index')->with(['linked_users'=> $linked_users, 'user_profile_data' => $user_profile_data, 'user_id' => $user->university_users->id, 'projects' => $projects, 'team_arr' => $team_arr, 'project_course_id' => $project_course_id, 'team_id' => $team_id, 'userId' => $userId, 'loginUserId' => $user->university_users->id]);
    }

    /**
     * Send message function.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function send_message(Request $request)
    {
        $user =  Auth::user();
        $user_profile_data = $this->userProfileObj->getUserProfile($user->id);
        $msg = Message::create([
            'sender_id' => $user->university_users->id,
            'receiver_id' => $request->receiver_id,
            'project_course_id' => $request->project_course_id,
            //'project_id' => $request->project_course_id,
            'team_id' => $request->team_id,
            'message' => $request->message,
            'is_read' => 0,
            'sent_at' => $request->sent_at
        ]);
        if($msg)
        {
            $user=UniversityUser::select('users.role_id','users.id','users.email','users.first_name')->where('university_users.id',  $request->receiver_id)
                        ->join('users','users.id','university_users.user_id')->first();
            if($user->role_id== Config::get('constants.roleTypes.faculty') || $user->role_id== Config::get('constants.roleTypes.ta') || $user->role_id== Config::get('constants.roleTypes.student'))
            {
               $res= $this->userObj->messageMailNotification($user->id, $user->email, $user->first_name);
               
            }
        }
        $returnHTML = view('messages.listing')->with(['message'=> $msg,'user'=> $user])->render();
        return response()->json(['response' => 'success','html'=>$returnHTML]);		

    }

    /**
     * Load messages of selected user from storage
     *
     * @param  int  $user_id|$project_id|$team_id
     * @return \Illuminate\Http\Response
     */
    public function load_messages($user_id, $project_id = null, $team_id = null, $read_at = null)
    {
        $team_arr = [];
        $user =  Auth::user();
        //update is_read
        $updateIsRead['is_read'] = 1;
        $updateIsRead['read_at'] = $read_at;
        DB::table('messages')
            ->where('sender_id', $user_id)
            ->where('receiver_id', $user->id)
            ->update($updateIsRead);
        //$linked_users = User::WhereIn('role_id', ['2', '3', '5'])->Where('status', '1')->get();
        $users_data =  DB::table('users')->select('users.id as id', 'users.first_name as first_name', 'users.last_name as last_name', 'users.profile_image as profile_image', 'roles.name as role_name')
            ->Join('roles', 'roles.id', '=', 'users.role_id')
            //->Join('messages', 'messages.receiver_id', '=', 'users.id')
            ->WhereIn('role_id', ['2', '3', '4', '5'])
            ->where('users.id', '!=', $user->id)
            ->Where('users.status', '1')->get();
            
            $linked_users = [];
        foreach($users_data as $key => $users){
            $linked_users[$key]['id'] = $users->id;
            $linked_users[$key]['first_name'] = $users->first_name;
            $linked_users[$key]['last_name'] = $users->last_name;
            $linked_users[$key]['profile_image'] = $users->profile_image;
            $linked_users[$key]['role_name'] = $users->role_name;
            $msgCount = $this->messageObj->message_count($users->id, $user->id);
            if($msgCount > 0){
                $unreadMsgCount = $msgCount;
                $newClass = 'msg-bg-color';
            }else{
                $unreadMsgCount = $newClass = '';
            }
            $linked_users[$key]['msg_count'] = $unreadMsgCount;
            $linked_users[$key]['unreadMsgClass'] = $newClass;
        }
        $user_profile_data = $this->userProfileObj->getUserProfile($user->id);
        $messages = DB::select( DB::raw('SELECT sender.first_name,sender.id as sender_id, receiver.id as receiver_id,messages.message,messages.sent_at,receiver.profile_image
            FROM messages
            LEFT JOIN users AS sender ON messages.sender_id = sender.id
            LEFT JOIN users AS receiver ON messages.receiver_id = receiver.id
            LEFT JOIN user_profiles AS profile ON profile.user_id = sender.id
            /*WHERE messages.sender_id = '.$user_id.' OR messages.receiver_id = '.$user_id.' AND  messages.sender_id = '.$user->id.' OR messages.receiver_id = '.$user->id.'*/
            WHERE messages.receiver_id = '.$user_id.' AND  messages.sender_id = '.$user->id.' OR messages.receiver_id = '.$user->id.'
            ORDER BY messages.id ASC'));
        $returnHTML = view('messages.listing')->with(['messages'=> $messages,'user'=> $user])->render();

        $projects = $this->projectObj->getProjects($user->id, $user->role_id);
        
        $teams_details = (!empty($project_id)) ? $this->projectObj->getProjectTeams($project_id, $user->id, '', $isDeleted=2) : [];
        if (!empty($teams_details)) {
            foreach ($teams_details as $count_team => $team_detail_arr) {
                $team_arr[$team_detail_arr->course_name][] = $team_detail_arr;
            }
        }

        return view('messages.index')->with(['linked_users'=> $linked_users,'html'=>$returnHTML, 'user_profile_data' => $user_profile_data, 'user_id' => $user_id, 'projects' => $projects, 'team_arr' => $team_arr, 'project_id' => $project_id, 'team_id' => $team_id]);
        //return response()->json(array('response' => 'success', 'html'=>$returnHTML));
       
    }

    /**
     * Load messages of selected user from storage
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function load_message(Request $request)
    {
        $user_profile_data = $this->userProfileObj->getUserProfileByUniversityUser($request->user_id);
        $userDesc =  DB::table('teams')->select('teams.name', 'courses.prefix', 'courses.number', 'courses.section', 'projects.title')
            ->Join('project_courses', 'project_courses.id', '=', 'teams.project_course_id')
            ->Join('courses', 'courses.id', '=', 'project_courses.course_id')
            ->Join('projects', 'projects.id', '=', 'project_courses.project_id')
            ->where('teams.id', $request->team_id)
            ->where('project_courses.id', $request->project_course_id)->get()->toArray();
        
        //check communication setting
        $desc = '';
        if(Auth::user()->role_id == 4){
            $checkUser = UniversityUser::with('university_users')->where('id',$request->user_id)->first();
            if($checkUser->university_users->role_id == 4){
                $result = $this->messageObj->talkToOtherTeamMembers($request->project_course_id, $request->team_id);
                if($result == 0){
                    $desc = 'You can not chat with other team member.';
                    $chatStatus = 0;
                }else{
                    //$desc = 'Chatting with <b>'.$user_profile_data->university_users->first_name.' '.$user_profile_data->university_users->last_name.'</b> in <b>'.$userDesc[0]->prefix.' '.$userDesc[0]->number.' '.$userDesc[0]->section.', '.$userDesc[0]->title.', '.$userDesc[0]->name.'</b> <hr>';
                    $chatStatus = 1;
                }
            }else{
                if($checkUser->university_users->role_id == 2){
                    $checkMessage = $this->messageObj->checkMessage($request->user_id, $request->project_course_id);
                    if($checkMessage > 0){
                        //$desc = 'Chatting with <b>'.$user_profile_data->university_users->first_name.' '.$user_profile_data->university_users->last_name.'</b> in <b>'.$userDesc[0]->prefix.' '.$userDesc[0]->number.' '.$userDesc[0]->section.', '.$userDesc[0]->title.', '.$userDesc[0]->name.'</b> <hr>';
                        $chatStatus = 1;
                    }else{
                        $result = $this->messageObj->talkToClientOrInstructors($request->project_course_id, 3);
                        if($result == 0){
                            $desc = 'You can not chat with client.';
                            $chatStatus = 0;
                        }else{
                            //$desc = 'Chatting with <b>'.$user_profile_data->university_users->first_name.' '.$user_profile_data->university_users->last_name.'</b> in <b>'.$userDesc[0]->prefix.' '.$userDesc[0]->number.' '.$userDesc[0]->section.', '.$userDesc[0]->title.', '.$userDesc[0]->name.'</b> <hr>';
                            $chatStatus = 1;
                        }
                    }
                }else{
                    if($checkUser->university_users->role_id == 1){
                        $chatStatus = 1;
                    }else{
                        $checkMessage = $this->messageObj->checkMessage($request->user_id, $request->project_course_id);
                        if($checkMessage > 0){
                            //$desc = 'Chatting with <b>'.$user_profile_data->university_users->first_name.' '.$user_profile_data->university_users->last_name.'</b> in <b>'.$userDesc[0]->prefix.' '.$userDesc[0]->number.' '.$userDesc[0]->section.', '.$userDesc[0]->title.', '.$userDesc[0]->name.'</b> <hr>';
                            $chatStatus = 1;
                        }else{
                            $result = $this->messageObj->talkToClientOrInstructors($request->project_course_id, 2);
                            if($result == 0){
                                $desc = 'You can not chat with instructor.';
                                $chatStatus = 0;
                            }else{
                                //$desc = 'Chatting with <b>'.$user_profile_data->university_users->first_name.' '.$user_profile_data->university_users->last_name.'</b> in <b>'.$userDesc[0]->prefix.' '.$userDesc[0]->number.' '.$userDesc[0]->section.', '.$userDesc[0]->title.', '.$userDesc[0]->name.'</b> <hr>';
                                $chatStatus = 1;
                            }
                        }
                    }
                }
            }
        }else{
            //$desc = 'Chatting with <b>'.$user_profile_data->university_users->first_name.' '.$user_profile_data->university_users->last_name.'</b> in <b>'.$userDesc[0]->prefix.' '.$userDesc[0]->number.' '.$userDesc[0]->section.', '.$userDesc[0]->title.', '.$userDesc[0]->name.'</b> <hr>';
            $chatStatus = 1;
        }
        $user_id = $request->user_id;
        
        $user =  Auth::user();
        $msgCount = $this->messageObj->message_count($user_id, $user->university_users->id);
        //update is_read
        $updateIsRead['is_read'] = 1;
        $updateIsRead['read_at'] = $request->read_at;
        DB::table('messages')
            ->where('sender_id', $user_id)
            ->where('receiver_id', $user->university_users->id)
            ->update($updateIsRead);
        if($request->searchKey != ''){
            $like = "%".$request->searchKey."%";
            //dd($like);
            if(Auth::user()->role_id == 1){
                $messages = DB::select( DB::raw('SELECT CONCAT(sender.first_name, " ", sender.last_name) as first_name, sender.id as sender_id, receiver.id as receiver_id,messages.message,messages.sent_at,sender.profile_image
                FROM messages
                LEFT JOIN university_users AS uniSender ON messages.sender_id = uniSender.id
                LEFT JOIN university_users AS uniReceiver ON messages.receiver_id = uniReceiver.id
                LEFT JOIN users AS sender ON uniSender.user_id = sender.id
                LEFT JOIN users AS receiver ON uniReceiver.user_id = receiver.id
                LEFT JOIN user_profiles AS profile ON profile.user_id = sender.id
                WHERE (messages.sender_id = '.$user_id.' AND messages.receiver_id = '.$user->university_users->id.') OR (messages.sender_id = '.$user->university_users->id.' AND messages.receiver_id = '.$user_id.') AND messages.message like '.$like.'
                ORDER BY messages.id ASC'));
            }else{
                $messages = DB::select( DB::raw('SELECT CONCAT(sender.first_name, " ", sender.last_name) as first_name, sender.id as sender_id, receiver.id as receiver_id,messages.message,messages.sent_at,sender.profile_image
                FROM messages
                LEFT JOIN university_users AS uniSender ON messages.sender_id = uniSender.id
                LEFT JOIN university_users AS uniReceiver ON messages.receiver_id = uniReceiver.id
                LEFT JOIN users AS sender ON uniSender.user_id = sender.id
                LEFT JOIN users AS receiver ON uniReceiver.user_id = receiver.id
                LEFT JOIN user_profiles AS profile ON profile.user_id = sender.id
                WHERE (messages.sender_id = '.$user_id.' AND messages.receiver_id = '.$user->university_users->id.') OR (messages.sender_id = '.$user->university_users->id.' AND messages.receiver_id = '.$user_id.') AND messages.project_course_id = '.$request->project_course_id.' AND messages.team_id = '.$request->team_id.' AND messages.message like '.$like.'
                ORDER BY messages.id ASC'));
            }
        }else{
            if(Auth::user()->role_id == 1){
                $messages = DB::select( DB::raw('SELECT CONCAT(sender.first_name, " ", sender.last_name) as first_name, sender.id as sender_id, receiver.id as receiver_id,messages.message,messages.sent_at,sender.profile_image
                FROM messages
                LEFT JOIN university_users AS uniSender ON messages.sender_id = uniSender.id
                LEFT JOIN university_users AS uniReceiver ON messages.receiver_id = uniReceiver.id
                LEFT JOIN users AS sender ON uniSender.user_id = sender.id
                LEFT JOIN users AS receiver ON uniReceiver.user_id = receiver.id
                LEFT JOIN user_profiles AS profile ON profile.user_id = sender.id
                /*WHERE messages.sender_id = '.$user_id.' OR messages.receiver_id = '.$user_id.' AND  messages.sender_id = '.$user->id.' OR messages.receiver_id = '.$user->id.'*/
                WHERE (messages.sender_id = '.$user_id.' AND messages.receiver_id = '.$user->university_users->id.') OR (messages.sender_id = '.$user->university_users->id.' AND messages.receiver_id = '.$user_id.') 
                ORDER BY messages.id ASC'));
            }else{
                if($request->project_course_id == '0'){
                    $messages = DB::select( DB::raw('SELECT CONCAT(sender.first_name, " ", sender.last_name) as first_name, sender.id as sender_id, receiver.id as receiver_id,messages.message,messages.sent_at,sender.profile_image
                    FROM messages
                    LEFT JOIN university_users AS uniSender ON messages.sender_id = uniSender.id
                    LEFT JOIN university_users AS uniReceiver ON messages.receiver_id = uniReceiver.id
                    LEFT JOIN users AS sender ON uniSender.user_id = sender.id
                    LEFT JOIN users AS receiver ON uniReceiver.user_id = receiver.id
                    LEFT JOIN user_profiles AS profile ON profile.user_id = sender.id
                    /*WHERE messages.sender_id = '.$user_id.' OR messages.receiver_id = '.$user_id.' AND  messages.sender_id = '.$user->id.' OR messages.receiver_id = '.$user->id.'*/
                    WHERE (messages.sender_id = '.$user_id.' AND messages.receiver_id = '.$user->university_users->id.') OR (messages.sender_id = '.$user->university_users->id.' AND messages.receiver_id = '.$user_id.')
                    ORDER BY messages.id ASC'));
                }else{
                    if($user_profile_data['university_users']['role_id'] == '1'){
                        $messages = DB::select( DB::raw('SELECT CONCAT(sender.first_name, " ", sender.last_name) as first_name, sender.id as sender_id, receiver.id as receiver_id,messages.message,messages.sent_at,sender.profile_image
                        FROM messages
                        LEFT JOIN university_users AS uniSender ON messages.sender_id = uniSender.id
                        LEFT JOIN university_users AS uniReceiver ON messages.receiver_id = uniReceiver.id
                        LEFT JOIN users AS sender ON uniSender.user_id = sender.id
                        LEFT JOIN users AS receiver ON uniReceiver.user_id = receiver.id
                        LEFT JOIN user_profiles AS profile ON profile.user_id = sender.id
                        /*WHERE messages.sender_id = '.$user_id.' OR messages.receiver_id = '.$user_id.' AND  messages.sender_id = '.$user->id.' OR messages.receiver_id = '.$user->id.'*/
                        WHERE (messages.sender_id = '.$user_id.' AND messages.receiver_id = '.$user->university_users->id.') OR (messages.sender_id = '.$user->university_users->id.' AND messages.receiver_id = '.$user_id.') 
                        ORDER BY messages.id ASC'));
                    }else{
                        $messages = DB::select( DB::raw('SELECT CONCAT(sender.first_name, " ", sender.last_name) as first_name, sender.id as sender_id, receiver.id as receiver_id,messages.message,messages.sent_at,sender.profile_image
                        FROM messages
                        LEFT JOIN university_users AS uniSender ON messages.sender_id = uniSender.id
                        LEFT JOIN university_users AS uniReceiver ON messages.receiver_id = uniReceiver.id
                        LEFT JOIN users AS sender ON uniSender.user_id = sender.id
                        LEFT JOIN users AS receiver ON uniReceiver.user_id = receiver.id
                        LEFT JOIN user_profiles AS profile ON profile.user_id = sender.id
                        /*WHERE messages.sender_id = '.$user_id.' OR messages.receiver_id = '.$user_id.' AND  messages.sender_id = '.$user->id.' OR messages.receiver_id = '.$user->id.'*/
                        WHERE (messages.sender_id = '.$user_id.' AND messages.receiver_id = '.$user->university_users->id.') OR (messages.sender_id = '.$user->university_users->id.' AND messages.receiver_id = '.$user_id.') AND messages.project_course_id = '.$request->project_course_id.' AND messages.team_id = '.$request->team_id.'
                        ORDER BY messages.id ASC'));
                    }
                }
            }
            //dd($messages);
        }
        $returnHTML = view('messages.listing')->with(['messages'=> $messages,'user'=> $user])->render();
        //dd($returnHTML);
        //return $returnHTML;
        //return view('messages.index')->with(['linked_users'=> $linked_users,'html'=>$returnHTML, 'user_profile_data' => $user_profile_data, 'user_id' => $user_id]);
        return response()->json(array('response' => 'success', 'html'=>$returnHTML, 'msgCount' => $msgCount, 'userDesc' => $desc, 'chatStatus' => $chatStatus));
       
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
     * Upload doc 
     * @param  \Illuminate\Http\Request  $request
     * @return Json
     */
     public function upload_doc(Request $request){
        
        $validator = Validator::make($request->all(), ['avatar' => 'required|mimes:jpeg,png,jpg,gif,svg,webp,doc,docx,pdf,csv']);
        
        if ($validator->fails())
        {
            $response =  ['msg' => $validator->errors()->first(), 'status' => 400];
        }
        
        $image = $request->file('avatar');
        $avatar = $request->img_name . time() . '.' . $image->getClientOriginalExtension();
        $destinationPath = base_path('/public/chat');
        $image->move($destinationPath, $avatar);
        $user =  Auth::user();
        $msg = Message::create([
            'sender_id' => $user->university_users->id,
            'receiver_id' => 3,
            'message' => '',
            'message_file'=>$avatar,
            'is_read' => 0,
            'sent_at' => Carbon::now()
        ]);
        $returnHTML = view('messages.listing')->with('message', $msg)->render();
        $response = ['status' => '200','html'=>$returnHTML];
        
        return response()->json($response);
            
    }

    /**
     * retrive unread messages from storage
     * @param  \Illuminate\Http\Request  $request
     * @return Json
     */
    public function load_unread_msg(Request $request)
    {
        $user_id = $request->user_id;
        $individualCount = $this->messageObj->totalIndividualMessageCount($user_id);
        if($individualCount > 0){
            $update = $this->messageObj->allUnseenMessageCountUpdate();
        }
        $msgCount = $this->messageObj->totalUnreadMessageCount($user_id);
        $requestCount = $this->messageObj->totalUnreadRequestCount();
        $pendingReviewRequestCount = $this->messageObj->pendingReviewRequestCount();
        //$response = ['status' => '200','count'=>$msgCount];
        return response()->json(array('response' => 'success','count'=>$msgCount,'requestCount'=>$requestCount, 'pendingReviewRequestCount' => $pendingReviewRequestCount));
    }

    /**
     * save a new help request and send help message to admins
     * @param \Illuminate\Http\Request  $request
     * @return JSON
     */
    public function help_request(Request $request)
    {
        $result = HelpRequest::create([
            'created_by' => Auth::user()->university_users->id,
            'name' => $request->sender_name,
            'email' => $request->sender_email,
            'message' => $request->help_message,
            'page_url' => $request->page_url,
            'created_at' => $request->created_at,
            'updated_at' => $request->created_at
        ]);
        
        $adminsList = $this->messageObj->getAdminList();       
        foreach ($adminsList as $adminList) {
            $mailTo = $adminList->email;
            $mailSubject = 'CapstonePro Help Request Submitted - '.$request->sender_name;
            $content1 = $request->page_url;
            $content2 = $request->sender_name;
            $content3 = $request->sender_email;
            $content4 = $request->help_message;
            $content5 = date('F d, Y H:i A', strtotime($request->created_at));
            $view = View::make('email/helpRequestMail', ['admin_name' => $adminList->first_name.' '.$adminList->last_name, 'content1' => $content1, 'content2' => $content2, 'content3' => $content3, 'content4' => $content4, 'content5' => $content5]);
            $mailMsg = $view->render();
            send_mail_with_bcc($mailTo, $mailSubject, $mailMsg);
        }  
        // $mailTo = 'info@netflygroup.com';
        // $mailSubject = 'CapstonePro Help Request Submitted - '.$request->sender_name;
        // $content1 = $request->page_url;
        // $content2 = $request->sender_name;
        // $content3 = $request->sender_email;
        // $content4 = $request->help_message;
        // $content5 = date('F d, Y H:i A', strtotime($request->created_at));
        // $view = View::make('email/helpRequestMail', ['admin_name' => 'Admin', 'content1' => $content1, 'content2' => $content2, 'content3' => $content3, 'content4' => $content4, 'content5' => $content5]);
        // $mailMsg = $view->render();
        // send_mail($mailTo, $mailSubject, $mailMsg);
    }
}

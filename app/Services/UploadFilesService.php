<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\File;
use App\Models\Team;
use App\Models\TeamStudent;
use App\Models\UserNotification;
use App\Models\ProjectCourse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Phppot\DataSource;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Mail\CreatePassword;
use View;
class UploadFilesService
{

    /**
     * Upload file in file table( file_id and their type ).
     * @param sting $file, $entity_type, $user_id, $entity_id, $path
     * @return Object
     */
    public function uploadFile($file, $entity_type, $user_id, $entity_id, $path, $fileType, $is_visible = false, $createdAt=null, $updatedAt=null)
    {
      
        try {
            if($is_visible == 'on'){
                $is_visible = 1;
            }else{
                $is_visible = 0;
            }
            $project_file = $file['file'];
            list($file_original_name) = explode('.', $file['file']->getClientOriginalName());
            $file_original_name = str_replace(' ', '-', $file_original_name);
            $file_name = $file_original_name . '_' . time() . '_.' . $project_file->getClientOriginalExtension();
            $destinationPath = base_path('public/projects/' . $path);
            $project_file->move($destinationPath, $file_name);
            $data = array(
                'name' => $file_name,
                'entity_id' => $entity_id,
                'entity_type' => $entity_type,
                'location' => 'public/projects/' . $path,
                'description' => '',
                'mime_type' => $fileType,
                'created_by' => $user_id,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
                'is_visibleToStudents' => $is_visible
            );
            if($entity_type == 'media' && auth()->user()->role_id == '4'){
                $data = array(
                    'name' => $file_name,
                    'entity_id' => $entity_id,
                    'entity_type' => $entity_type,
                    'location' => 'public/projects/' . $path,
                    'description' => '',
                    'mime_type' => $fileType,
                    'created_by' => $user_id,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                    'is_visibleToStudents' => '1'
                );
            }
            //dd($data);
            $files = File::create($data);
           

            if($entity_type=='team_files')
            {
                /**
                 * code for student notification
                */
                  // DB::enableQueryLog(); // Enable query log
                  $datas=TeamStudent::with(['university_students.university_users'])->where('team_id', $entity_id)->get();
                  // dd(DB::getQueryLog());
                  foreach ($datas as $data) 
                  {                
                      $user=$data->university_students->university_users;
                       $userId=$user->id;
                      if($userId!=null)
                      {
                         $email=$user->email;
                         $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                                              ->where('status', 1)                               
                                                              ->where('notification_id', 30)->first();
                          if(isset($permissionCheck))
                          {
                              if($permissionCheck->status==1)
                              {
                                  $mailSubject="A New Project File Uploaded";
                                  $content = "This is an automated message. A team member has uploaded a new project file.";
                                  $view = View::make('email/adminProject', ['admin_name' => ucfirst($user->first_name) . ' ' . ucfirst($user->last_name), 'content' => $content]);
                                  $mailMsg = $view->render();
                                  //$mailMsg="Dear ".ucfirst($user->first_name).", This is an automated message. A team member has uploaded a new project file.";
                                  send_mail($email, $mailSubject, $mailMsg);  
                                  //return redirect()->back()->with('success', 'File uploaded successfully.');
                                  //echo"<br>". "mails sent on ".$email."<br>";                  
                                      
                              }    
                              // dd(DB::getQueryLog()); // Show results of log               
                          }else{
                              echo"<br>".  "not in usernotification";
                          }                    
                      }  
                }
/**
 * code for faculty or ta notification
 */
                $data=Team::select('fauser.email as fa_email', 'fauser.first_name as fa_first_name', 'fauser.last_name as fa_last_name', 'fauser.id as fa_user_id', 'tauser.email as ta_email', 'tauser.first_name as ta_first_name', 'tauser.last_name as ta_last_name', 'tauser.id as ta_user_id')->join('project_courses', 'project_courses.id', 'teams.project_course_id')
                ->leftjoin('courses', 'courses.id', 'project_courses.course_id')
                ->leftjoin('university_users as fa', 'fa.id', 'courses.faculty_id')
                ->leftjoin('university_users as ta', 'ta.id', 'courses.ta_id')
                ->leftjoin('users as fauser', 'fauser.id', 'fa.user_id')
                ->leftjoin('users as tauser', 'tauser.id', 'ta.user_id')
                ->where('teams.id',$entity_id)->first();
                if($data->fa_user_id!=null)
                {
                    $userId=$data->fa_user_id;
                    $mailTo=$data->fa_email;
                    $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                                        ->where('status', 1)                               
                                                        ->where('notification_id', 9)->first();
                    if(isset($permissionCheck) && isset($data->fa_email))
                    {
                        if($permissionCheck->status)
                        {
                            if($data->fa_email)
                                {
                                    $mailSubject="A Team Has Uploaded Project File";
                                    $content = "This is an automated message. A team member has uploaded a new project file.";
                                    $view = View::make('email/adminProject', ['admin_name' => ucfirst($data->fa_first_name) . ' ' . ucfirst($data->fa_last_name), 'content' => $content]);
                                    $mailMsg = $view->render();
                                    //$mailMsg="Dear ".ucfirst($data->fa_first_name).", A File has been uploaded in your Project by the team";
                                    send_mail($mailTo, $mailSubject, $mailMsg);  
                                    //return redirect()->back()->with('success', 'File uploaded successfully.');
                                    // $result[]="Faculty Mail sent";                   
                                }
                        }                   
                    }

                }
                if($data->ta_user_id!=null)
                {
                    $userId=$data->ta_user_id;
                    $mailTo=$data->ta_email;
                    $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                                                    ->where('status', 1)  
                                                                    ->where('notification_id', 19)->first();
                    if(isset($permissionCheck) && isset($data->ta_email))
                    {
                        if($permissionCheck->status)
                        {
                            if($data->ta_email)
                                {
                                    $mailSubject="A Team Has Uploaded Project File";
                                    $content = "This is an automated message. A team member has uploaded a new project file.";
                                    $view = View::make('email/adminProject', ['admin_name' => ucfirst($data->ta_first_name) . ' ' . ucfirst($data->ta_last_name), 'content' => $content]);
                                    $mailMsg = $view->render();
                                    //$mailMsg="Dear ".ucfirst($data->ta_first_name).", A File has been uploaded in your Project by the team";
                                    send_mail($mailTo, $mailSubject, $mailMsg);   
                                    // $result[]="Ta Mail sent";                  
                                }
                        }                   
                    }
                }
                
                

            }
           return $files->id;
        } catch (Throwable $e) {
            return false;
        }
    }

 
   /**
     * Upload file for Change Request
     * @param sting $file, $entity_type, $user_id, $entity_id, $path
     * @return Object
     */
    public function uploadCRfile($file, $entity_type, $user_id, $entity_id, $path, $fileType, $fileID=null, $createdAt, $updatedAt)
    {
        try {
            $project_file = $file['file'];
            list($file_original_name) = explode('.', $file['file']->getClientOriginalName());
            $file_original_name = str_replace(' ', '-', $file_original_name);
            $file_name = $file_original_name . '_' . time() . '_.' . $project_file->getClientOriginalExtension();
            $destinationPath = base_path('public/projects/' . $path);
            $project_file->move($destinationPath, $file_name);
            if(!empty($fileID)){
                DB::table('files')->where('id', $fileID)->delete();
            }
                $data = array(
                    'name' => $file_name,
                    'entity_id' => $entity_id,
                    'entity_type' => $entity_type,
                    'location' => 'public/projects/' . $path,
                    'description' => '',
                    'mime_type' => $fileType,
                    'created_by' => $user_id,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt
                );
                $files = File::create($data);
         

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Upload bulk student files
     * @param Array $request
     * @param Sting $user_id
     * @return Object
     */
    public function uploadStudentBulkFile($request, $user_id)
    {

        $allowedFileType = [
            'application/vnd.ms-excel',
            'text/xls',
            'text/xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        $file_ext = $request->file('file')->extension();

        if (in_array($request->file('file')->getMimeType(), $allowedFileType)) {
            $file_name = time() . '_' . $request->file('file')->getClientOriginalName();
            $project_file = $request->file('file');
            $temp_path = $request->file('file')->getPathName();
            $targetPath = base_path('public/projects/temp/');
            $project_file->move($targetPath, $file_name);
            move_uploaded_file($file_name, $targetPath);

            if ($file_ext == 'xls') {
                $Reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
            } else {
                $Reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            }
            $spreadSheet = $Reader->load($targetPath . '/' . $file_name);
            $excelSheet = $spreadSheet->getActiveSheet();
            $spreadSheetAry = $excelSheet->toArray();
            $sheetCount = count($spreadSheetAry);

            $invalid_students_arr = [];
            $activate_students_arr = [];
            $valid_student_counter = 0;
            if($excelSheet->getCell('A1') != 'User Name'){
                $error['type'] = 'error';
                $error['message'] = "File format is not valid. Please download sample file.";
                return $error;
            }
            if($excelSheet->getCell('B1') != 'First Name'){
                $error['type'] = 'error';
                $error['message'] = "File format is not valid. Please download sample file.";
                return $error;
            }
            if($excelSheet->getCell('C1') != 'Last Name'){
                $error['type'] = 'error';
                $error['message'] = "File format is not valid. Please download sample file.";
                return $error;
            }
            if($excelSheet->getCell('D1') != 'Email'){
                $error['type'] = 'error';
                $error['message'] = "File format is not valid. Please download sample file.";
                return $error;
            }
            for ($i = 1; $i < $sheetCount; $i++) {
                $user_name = (!empty($spreadSheetAry[$i][0])) ? $spreadSheetAry[$i][0] : '';
                $first_name = (!empty($spreadSheetAry[$i][1])) ? $spreadSheetAry[$i][1] : '';
                $last_name = (!empty($spreadSheetAry[$i][2])) ? $spreadSheetAry[$i][2] : '';
                $email = (!empty($spreadSheetAry[$i][3]) != '' && filter_var($spreadSheetAry[$i][3], FILTER_VALIDATE_EMAIL)) ? $spreadSheetAry[$i][3] : '';
                if($email){
                    $is_student_validated = $this->_validate_excel_cell_values($email, $user_name, $first_name, $last_name, $user_id, $request->course_id, $request->created_at, $request->updated_at);
                    if($is_student_validated == 'active'){
                        $activate_students_arr[$i]['user_name'] = $user_name;
                        $activate_students_arr[$i]['first_name'] = $first_name;
                        $activate_students_arr[$i]['last_name'] = $last_name;
                        $activate_students_arr[$i]['email'] = $email;
                    }elseif ($is_student_validated == true) {
                        ++$valid_student_counter;
                    } else {
                        $invalid_students_arr[$i]['user_name'] = $user_name;
                        $invalid_students_arr[$i]['first_name'] = $first_name;
                        $invalid_students_arr[$i]['last_name'] = $last_name;
                        $invalid_students_arr[$i]['email'] = $email;
                    }
                }
            }
            unlink($targetPath.$file_name);
        } else {
            $type = "error";
            $message = "Invalid File Type. Upload Excel File.";
        }

        $invalid_students_list = '';
        $activate_students_list = '';
        if (!empty($activate_students_arr)) {
            foreach ($activate_students_arr as $key => $value) {
                $value['email'] = (empty($value['email'])) ? '<span class="error">Invalid Email</span>' : $value['email'];
                $activate_students_list .= '<tr>';
                $activate_students_list .= '<td>' . $value['user_name'] . '</td><td>' . $value['first_name'] . '</td><td>' . $value['last_name'] . '</td><td>' . $value['email'] . '</td>';
                $activate_students_list .= '</tr>';
            }
        }
        if (!empty($invalid_students_arr)) {
            foreach ($invalid_students_arr as $key => $value) {
                $value['email'] = (empty($value['email'])) ? '<span class="error">Invalid Email</span>' : $value['email'];
                $invalid_students_list .= '<tr>';
                $invalid_students_list .= '<td>' . $value['user_name'] . '</td><td>' . $value['first_name'] . '</td><td>' . $value['last_name'] . '</td><td>' . $value['email'] . '</td>';
                $invalid_students_list .= '</tr>';
            }
        }
        $activate_students_list = ($activate_students_list == '') ? '' : '<span class="success">The following students were activated and assigned to the course.<br><table class="table error-failed">' . $activate_students_list . '</table></span>';
        $invalid_students_list = ($invalid_students_list == '') ? '' : '<span class="error">The following duplicate records already exist and were not uploaded. They were assigned to the course.<br><table class="table error-failed">' . $invalid_students_list . '</table></span>';

        $dynamic_msg = (($sheetCount - 1) == $valid_student_counter) ? 'All students uploaded : ' :  'Total students uploaded : ';
        if($valid_student_counter > 1){
            $stu='students';
        }else{
            $stu='student';
        }
        $return['inserted'] = ($valid_student_counter == 0) ? '' :  $valid_student_counter . ' '.$stu.' uploaded successfully';
        $return['activate'] =  $activate_students_list;
        $return['failed'] =  $invalid_students_list;
        $return['message'] =  (!empty($message)) ? $message : '';

        return $return;
    }

    /**
     * Validate the email and username saved already to a user
     * @param Stirng $email, $user_name, $first_name, $last_name, 
     * @param Int $user_id, $course_id
     * @return Object
     */
    protected function _validate_excel_cell_values($email, $user_name, $first_name, $last_name, $user_id, $course_id, $created_at, $updated_at)
    {
        $user = [];
        $student_detail = DB::table('users')->select('users.id as id', 'university_users.id as uni_id', 'users.status as status', 'users.is_deleted as is_deleted')
            ->join('university_users', 'users.id', '=', 'university_users.user_id')
            ->where('email', $email)
            ->orWhere('user_name', $user_name)->get()->first();
        $user['first_name'] = $first_name;
        $user['last_name'] = $last_name;
        $user['email'] = $email;
        
        $university_users =  DB::table('university_users')->select('id')
            ->Where('user_id', $user_id)->first();
        
        if ($student_detail == null && !empty($email) && !empty($user_name) && !empty($first_name) && !empty($last_name)) {
            $new_student = array(
                'user_name' => strtolower($user_name),
                'last_name' => $last_name,
                'first_name' => $first_name,
                'email' => $email,
                'email_verification_token' => Str::random(32),
                'password' => Hash::make($user_name),
                'status' => 1,
                'role_id' => 4,
                'created_by' => Auth::user()->university_users->id,
                'created_at' => $created_at,
                'updated_at' => $updated_at
            );
            $user['email_verification_token'] = $new_student['email_verification_token'];
            $user = json_decode(json_encode($user), FALSE);
            
            DB::table('users')->insert($new_student);
            $insertedUserId = DB::getPdo()->lastInsertId();
            if($insertedUserId){
                Mail::to($email)->send(new CreatePassword($user));
            }
            $modelHasRoles = array(
                'role_id' => '4',
                'model_type' => 'App\Models\User',
                'model_id' => $insertedUserId
            );
            DB::table('model_has_roles')->insert($modelHasRoles);
            $university_user['user_id'] = $insertedUserId;
            $university_user['created_at'] = $created_at;
            DB::table('university_users')->insert($university_user);
            $insertedUniversityId = DB::getPdo()->lastInsertId();

            //insert into course_students table
            $course_student['course_id'] = $course_id;
            $course_student['student_id'] = $insertedUniversityId;
            $course_student['created_by'] = $university_users->id;
            $course_student['created_at'] = $created_at;
            DB::table('course_students')->insert($course_student);

            //update courses table student_count
            $studentCount = DB::table('course_students')->select('id')
                    ->where('course_id', $course_id)->count();

            $updateCount['student_count'] = $studentCount;
            $updateCount['updated_at'] = $updated_at;
            $affected = DB::table('courses')
                ->where('id', $course_id)
                ->update($updateCount);

            return true;
        } else {
            if($student_detail->is_deleted == 1){
                //dd($student_detail->is_deleted);
                $activateUser['is_deleted'] = 0;
                DB::table('users')
                    ->where('id', $student_detail->id)
                    ->update($activateUser);

                $course_students = DB::table('course_students')->select('id')
                ->where('course_id', $course_id)
                ->Where('student_id', $student_detail->uni_id)->get()->first();

                if($course_students == null){
                    $course_student['course_id'] = $course_id;
                    $course_student['student_id'] = $student_detail->uni_id;
                    $course_student['created_by'] = $university_users->id;
                    $course_student['created_at'] = $created_at;
                    DB::table('course_students')->insert($course_student);
    
                    //update courses table student_count
                    $studentCount = DB::table('course_students')->select('id')
                        ->where('course_id', $course_id)->count();
    
                    $updateCount['student_count'] = $studentCount;
                    $updateCount['updated_at'] = $updated_at;
                    $affected = DB::table('courses')
                        ->where('id', $course_id)
                        ->update($updateCount);
                }
                return 'active';
            }else{
                $course_students = DB::table('course_students')->select('id')
                ->where('course_id', $course_id)
                ->Where('student_id', $student_detail->uni_id)->get()->first();

                if($course_students == null){
                    $course_student['course_id'] = $course_id;
                    $course_student['student_id'] = $student_detail->uni_id;
                    $course_student['created_by'] = $university_users->id;
                    $course_student['created_at'] = $created_at;
                    DB::table('course_students')->insert($course_student);

                    //update courses table student_count
                    $studentCount = DB::table('course_students')->select('id')
                        ->where('course_id', $course_id)->count();

                    $updateCount['student_count'] = $studentCount;
                    $updateCount['updated_at'] = $updated_at;
                    $affected = DB::table('courses')
                        ->where('id', $course_id)
                        ->update($updateCount);
                    return true;
                }else{
                    return false;
                }
            }
        }
    }

 
 /**
     *Upload file for Task
     * @param sting $file, $entity_type, $user_id, $entity_id, $path
     * @return Object
     */
    public function uploadTaskfile($file, $entity_type, $user_id, $entity_id, $path, $fileType)
    {
        try {
            $project_file = $file['file'];
            list($file_original_name) = explode('.', $file['file']->getClientOriginalName());
            $file_original_name = str_replace(' ', '-', $file_original_name);
            $file_name = $file_original_name . '_' . time() . '_.' . $project_file->getClientOriginalExtension();
            $destinationPath = base_path('public/projects/' . $path);
            $project_file->move($destinationPath, $file_name);
            
            $data = array(
                'name' => $file_name,
                'entity_id' => $entity_id,
                'entity_type' => $entity_type,
                'location' => 'public/projects/' . $path,
                'description' => '',
                'mime_type' => $fileType,
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            );
            $files = File::create($data);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get file data 
     * @param int $id
     * @return Object
     */
    public function getFileData($id)
    {
        $fileData = File::where('id', $id)->get()->first();
        return $fileData;
    }

    public function sendMailNotification($entity_id = null)
    {
        $result = [];
        $datas=TeamStudent::with(['university_students.university_users'])->where('team_id', $entity_id)->get();
        // dd(DB::getQueryLog());
        foreach ($datas as $data) 
        {                
            $user=$data->university_students->university_users;
            $userId=$user->id;
            if($userId!=null)
            {
                $email=$user->email;
                $permissionCheck=UserNotification::select('status')->where('user_id',$userId)
                                                    ->where('status', 1)                               
                                                    ->where('notification_id', 30)->first();
                if(isset($permissionCheck))
                {
                    if($permissionCheck->status==1)
                    {
                        $mailSubject="A New Project File Uploaded";
                        $content = "This is an automated message. A team member has uploaded a new project file.";
                        $view = View::make('email/adminProject', ['admin_name' => ucfirst($user->first_name) . ' ' . ucfirst($user->last_name), 'content' => $content]);
                        $mailMsg = $view->render();
                        //$mailMsg="Dear ".ucfirst($user->first_name).", This is an automated message. A team member has uploaded a new project file.";
                        send_mail($email, $mailSubject, $mailMsg);  
                        //return redirect()->back()->with('success', 'File uploaded successfully.');
                        //echo"<br>". "mails sent on ".$email."<br>";                  
                        $result[]="Team file upload mail sent to students.";
                    }    
                    // dd(DB::getQueryLog()); // Show results of log               
                }else{
                    $result[]="Not in usernotification";
                }                    
            }  
        }
        return $result;
    }
}

<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use App\Services\EmailTemplateService;
use View;

class EmailTemplateController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:Manage Email Templates', ['only' => ['index', 'deactivatedEmailTemplates', 'destroy', 'active']]);
        $this->emailTemplateObj = new EmailTemplateService(); // email template Service object
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user_id = auth()->user()->id;
        $allEmailTemplates = $this->emailTemplateObj->getAllEmailTemplate();
        return view('email_templates.index', compact('allEmailTemplates', 'user_id'));
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
            $view_templates = $this->emailTemplateObj->alltemplates($request);
            $return['recordsFiltered'] = $return['recordsTotal'] = count($view_templates);
            $return['draw'] = $request->draw;
            $options = '';
            $editOption = '';

            foreach ($view_templates as $key => $temp) {
                $editOption = '<a class="edit-template del-active" edit-id = "' . $temp->id . '" data-temp-description-'.$temp->id.'="'.$temp->description.'">Edit</a> ';
                if($temp->is_deleted == 0){ $del = '<span class="mx-1">|</span> <a class="delete-email del-active" del-id = "'. $temp->id .'" >Deactivate</a>';}else{$del = '<span class="mx-1">|</span> <a class="active-email del-active" act-id = "'. $temp->id .'" >Activate</a>';}
                if(Auth::user()->role_id == 1){
                    $options = $editOption.$del;
                }
                if($temp->created_by == Auth::user()->university_users->id){
                    $options = $editOption.$del;
                }
                $data[$key]['name'] = '<span class="edit-temp-name-' . $temp->id . '">' . $temp->name . '</span>';
                $data[$key]['description'] = '<span class="edit-temp-description-' . $temp->id . '">' . $temp->description . '</span> ';
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
     * Add template
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function addEmailTemplate(Request $request){
        $data = $request->all();
        $questions = $this->emailTemplateObj->update_template($data, auth()->user()->id);
        return redirect()->route('allEmailTemplates')->with('success', $questions);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, $dateTime)
    {
        $temp = $this->emailTemplateObj->deleteTemplate($id, $dateTime);
        return redirect()->route('allEmailTemplates')
                        ->with('success','Email template deactivated successfully');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function deactivatedEmailTemplates()
    {
        $emailTemplateList = $this->emailTemplateObj->deactivatedEmailTemplates();
        return view('email_templates.deactivatedEmailTemplates', ['emailTemplateList' => $emailTemplateList]);
    }

    /**
     * Active the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function active($id)
    {
        $temp = $this->emailTemplateObj->activeTemplate($id);
        return redirect()->route('deactivatedEmailTemplates')
                        ->with('success','Email template activated successfully');
    }

    /**
     * Get dewcription of selected email template.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getEmailTemplateDescription(Request $request){
        $data = $request->all();
        
        $result = $this->emailTemplateObj->getEmailTemplateDescription($data);
        
        return response()->json($result);
    }

    /**
     * Sent email template to users.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function sentEmailTemplate(Request $request){
        $data = $request->all();
        
        $mailTo = $data['email'];
        if(!empty($data['manualEmailTemplate']) and $data['email_template_id'] == 0){
            $mailSubject = $data['manualEmailTemplate'];
        }else{
            $mailSubject = $data['name'];
        }
        $content = $data['emailTemplateDescription'];
        $view = View::make('email/emailTemplate', ['content' => $content]);
        $mailMsg = $view->render();
        send_mail($mailTo, $mailSubject, $mailMsg);
        return redirect()->back()->with('success', 'Email template sent successfully.');
    }
}
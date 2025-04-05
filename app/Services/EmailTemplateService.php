<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\EmailTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class EmailTemplateService
{
    /**
     * @description All Email Templates
     * @param int $user_id
     * @return boolean
     */
    public function getAllEmailTemplate()
    {
        try {
            $emailTemplates = EmailTemplate::select('*')->get();
        return $emailTemplates;
        }  catch (Throwable $e) {
            return false;
        }
    }

    /**
     * List all templates.
     * @param object $request
     * @return $request
     */

    public function alltemplates($request)
    {
        $keywords = trim($request->search['value']);
        $start = $request->start;
        $limit = $request->length;
        $order_index = $request->order[0]['column'];
        $order_by = $request->columns[$order_index]['data'];
        $sort_by = $request->order[0]['dir'];
        $queries =  EmailTemplate::with('usercreated_by.university_users', 'userupdated_by.university_users')->select('email_templates.id', 'email_templates.name', 'email_templates.description', 'email_templates.is_deleted', 'email_templates.created_by', 'email_templates.created_at', 'email_templates.updated_by', 'email_templates.updated_at')
           ->where('email_templates.is_deleted', '0');
        
        $keywords = trim($keywords);
        if (!empty($keywords)) {
            $search_words =  explode(' ', $keywords);
            $firstword = $search_words[0];
            $lastword = @$search_words[1];

            if (empty($lastword)) {
                $queries->Where(function ($query) use ($firstword) {
                    $query->where('email_templates.name', 'like', '%' . $firstword . '%')
                        ->orWhere('email_templates.description', 'like', '%' . $firstword . '%');
                });
            } else {
                $queries->Where(function ($query) use ($firstword, $lastword) {
                    $query->where('email_templates.name', 'like', '%' . $firstword . '%')
                        ->Where('email_templates.name', 'like', '%' . $lastword . '%')
                        ->orWhere('email_templates.description', 'like', '%' . $firstword . ' ' . $lastword . '%');
                });
            }
        }
        return $queries = $queries->groupBy('email_templates.id')->orderBy($order_by, $sort_by)
            ->limit($limit, $start)
            ->get();
    }

    /**
     * function for get all deactivated templates
     * @retrun object
     */
    public function deactivatedEmailTemplates(){
        $loginUserId=Auth::user()->id;
        $emailTemplates =  EmailTemplate::with('usercreated_by.university_users', 'userupdated_by.university_users')->select('email_templates.id', 'email_templates.name', 'email_templates.description', 'email_templates.is_deleted', 'email_templates.created_by', 'email_templates.created_at', 'email_templates.updated_by', 'email_templates.updated_at')
        ->where('is_deleted', 1)->get();
        return $emailTemplates;
    }

    /**
     * Save or update template
     * @param object $request, int $user_id
     * @return string
     */
    public function update_template($request, $user_id)
    {
        //dd($request);
        $university_user =  DB::table('university_users')->select('id')
            ->Where('user_id', $user_id)->first();
        //dd($university_user->id);
        $template = array(
            'name' => $request['name'],
            'description' => $request['description'],
        );
        
        if (!empty($request['id'])) {
            
            $template['updated_by'] = $university_user->id;
            $template['updated_at'] = $request['updated_at'];
            DB::table('email_templates')
                ->where('id', $request['id'])
                ->update($template);

            $eval_id = $request['id'];
            $msg = 'Email template updated successfully';
        } else {
            $template['created_by'] = $university_user->id;
            $template['updated_by'] = $university_user->id;
            $template['created_at'] = $request['created_at'];
            $template['updated_at'] = $request['updated_at'];
            DB::table('email_templates')->insert($template);

            $eval_id = DB::getPdo()->lastInsertId();
            $msg = 'Email template added successfully';
        }
        return $msg;
    }

    /** 
     * inactive template
     * @param int $id
     * @return boolean
     */
    public  function deleteTemplate($id, $dateTime)
    {
        DB::table('email_templates')->where('id', $id)->update(['is_deleted' => 1, 'updated_by' => Auth::user()->university_users->id, 'updated_at' => $dateTime]);
        return true;
    }

    /** 
     * active template
     * @param int $id
     * @return boolean
     */
    public  function activeTemplate($id)
    {
        DB::table('email_templates')->where('id', $id)->update(['is_deleted' => 0]);
        return true;
    }

    /**
     * @description All Email Templates
     * @param int $user_id
     * @return boolean
     */
    public function getAllActiveEmailTemplate()
    {
        try {
            $allActiveEmailTemplate = EmailTemplate::select('*')->where('is_deleted', 0)->get();
        return $allActiveEmailTemplate;
        }  catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @description of selected email template
     * @param int $id
     * @return boolean
     */
    public function getEmailTemplateDescription($data)
    {
        //dd($data['id']);
        try {
            $allActiveEmailTemplate = EmailTemplate::select('name', 'description')->where('id', $data['id'])->first();
        return $allActiveEmailTemplate;
        }  catch (Throwable $e) {
            return false;
        }
    }
}

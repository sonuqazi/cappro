<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserNotification;
use DB;
class UserNotificationController extends Controller {
    //


	/**
	 * Update user notification settings
	 * 
	 * @param \Illuminate\Http\Request  $request
	 * @return Json
	 */
    public function update_user_notification_settings(Request $request) {
		try {
            $value = ( $request['customInput'] == 0 ) ? 1 : 0;
			// DB::enableQueryLog();
	    	UserNotification::where('id', $request['notiID'])->update(array('status' => $request['customInput'], 'updated_at' => $request['updated_at']));
			// dd(DB::getQueryLog());
			return response()->json(['status' => 201, 'response' => 'success','value' => $value, 'msg'=>'Updated successfully.']);
    	} catch (Throwable $e) {
            return response()->json(['status' => 400, 'response' => 'error', 'msg' => $e]);
    	}
    }
}

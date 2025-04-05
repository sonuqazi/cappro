<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DiscussionLike;
use Illuminate\Support\Facades\Auth;
use App\Traits\DiscussionTrait;

class DiscussionLikesController extends Controller
{
    use DiscussionTrait;
    /**
     * Store a newly created like in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function like(Request $request)
    {
        $user = Auth::user();
        $data = array(
            'user_id' => $user->university_users->id,
            'discussion_id' =>$request->id,
            'created_at' =>$request->updated_at,
            'updated_at' =>$request->updated_at
        );
        $isLike = DiscussionLike::where('user_id',$user->university_users->id)->where('discussion_id',$request->id)->first();
        if(empty($isLike)){
            DiscussionLike::create($data);
            $class = 'liked';
        }else{
            DiscussionLike::where('id', $isLike->id)->delete();
            $class = '';
        }
       
        $count = DiscussionLike::where('discussion_id',$request->id)->count();
        if ($request->ajax()) {
            return response()->json(['response'=>'success','class'=>$class,'count' => $count]);
        }

        return redirect()->back();
    }

    public function discussionLikedUsers(Request $request)
    {
        $html = $hr = '';
        $likeByUsers =  $this->discussionLikedByUsers($request->discussions_id);
        if(count($likeByUsers) > 0){
            foreach($likeByUsers as $key => $users){
                //dd($users);
                $data[$key]['id'] = $users->user->id;
                $data[$key]['userName'] = $users->user->university_users->first_name.' '.$users->user->university_users->last_name;
                $data[$key]['profileImage'] = $users->user->university_users->profile_image;

            }
        
            $firsthalf = array_slice($data,0,2);
            $secondhalf = array_slice($data,2);
            
            if(count($firsthalf)>0){
                foreach($firsthalf as $key => $result){
                    $defaultAvtar = asset('img/default-avtar.png');
                    if($result['profileImage'] != '' && file_exists(public_path('avatar/'.$result['profileImage']))){
                        $img = asset('avatar/'.$result['profileImage']);
                        $profileImage = '<img class="rounded-circle" src="'.$img.'" width="20">';
                    }else{
                        $profileImage = '<img class="rounded-circle" src="'.$defaultAvtar.'" width="20">';
                    }
                    if(count($data) == 2){
                        $break=' and ';
                    }else{
                        $break=', ';
                    }
                    if($key == 0){
                        $html .= '<span>'.$result['userName'].'</span>';
                    }else{
                        $html .= $break.'<span>'.$result['userName'].'</span>';
                    }
                }
            }
            if(count($secondhalf) > 1){
                $count = ' and '.count($secondhalf).' Others like this';
                $html .=$count;
            }elseif(count($secondhalf) == 1){
                $count = ' and '.count($secondhalf).' Other like this';
                $html .=$count;
            }
            return $html;
        }
    }

}

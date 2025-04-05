<?php

namespace App\Traits;
use App\Models\Discussion;
use App\Models\DiscussionsComment;
use App\Models\DiscussionsCommentLike;
use App\Models\DiscussionLike;
use App\Models\File;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;

trait DiscussionTrait {

    /*
    * Add a new discussion
    */
    public function startDiscussion($data) {

        $discussion = Discussion::create($data);
        return $discussion;
    }

    /*
    * Get all discussions
    */
    public function getDiscussions($data) {

        $discussion = Discussion::where($data);
        return $discussion;
    }

    /*
    * Add a new discussion
    */
    public function discussionComment($data) {
        
         $discussion = DiscussionsComment::create($data);
         return $discussion;
    }

    /*
    * Discossion comments count
    */
    public function discussionCommentCount($discussion_id) {
        
        $count = DiscussionsComment::where('discussion_id',$discussion_id)->count();
        return $count;
   }

    public function getUserDiscussions($entity_id, $user_id = null,$entity_type) {

         $discussion = Discussion::with(['comments.commentLikes','discussion_files','likes','teams'])
                        ->where(function($query) use ($entity_id,$entity_type) {
                            if ($entity_id != NULL) {
                            $query->where('discussions.entity_id', $entity_id);
                            $query->where('discussions.entity_type', $entity_type);
                            }
                        })
                        // ->where(function($query) use ($user_id) {
                        //     if($user_id != ''){
                        //         $query->where('discussions.created_by', $user_id);
                        //     }
                        // })
                        ->join('university_users', 'university_users.id', '=', 'discussions.created_by')
                        ->join('users', 'users.id', '=', 'university_users.user_id')
                        ->select('discussions.*', 'users.first_name','users.last_name' ,'users.profile_image')
                        ->where('discussions.status',1)
                        ->where('discussions.is_deleted',0)
                        ->orderBy('discussions.created_at', 'desc')
                        ->get();
         return $discussion;
    }

    public function getAllProjectDiscussions($projectCourseId, $user_id = null,$entity_type) {
        $teams = Team::where('teams.project_course_id', $projectCourseId)
        ->where('teams.is_deleted', 0)->select('teams.id')->get();
        
        $discussion = Discussion::with(['comments.commentLikes','discussion_files','likes','teams'])
        ->where(function($query) use ($teams,$entity_type) {
                           $query->whereIn('discussions.entity_id', $teams);
                           $query->where('discussions.entity_type', $entity_type);
                       })
                       ->join('university_users', 'university_users.id', '=', 'discussions.created_by')
                       ->join('users', 'users.id', '=', 'university_users.user_id')
                       ->select('discussions.*', 'users.first_name','users.last_name' ,'users.profile_image')
                       ->where('discussions.status',1)
                       ->where('discussions.is_deleted',0)
                       ->orderBy('discussions.created_at', 'desc')
                       ->get();
        return $discussion;
   }

    public  function deleteDiscussion ($id, $dateTime){
        Discussion::where('id', $id)->update(['status' => 0,'is_deleted'=> 1, 'updated_at' => $dateTime]);
        return true;
    }
    
    public  function deleteDiscussionComment ($id, $dateTime){
        DiscussionsCommentLike::where('discussions_comment_id', $id)->delete();
        DiscussionsComment::where('id', $id)->delete();
        
        return true;
    }
        
    public function discussionLikedByUsers($discussion_id)
    {
        $likeByUsers = DiscussionLike::with(['user.university_users'])->where('discussion_id',$discussion_id)->orderBy('id','desc')->get();
        return $likeByUsers;
    }

    public function commentLikedByUsers($discussions_comment_id)
    {
        $likeByUsers = DiscussionsCommentLike::with(['user.university_users'])->where('discussions_comment_id',$discussions_comment_id)->orderBy('id','desc')->get();
        return $likeByUsers;
    }

    public function getAllDiscussions($semester_id){
        if(Auth::user()->role_id == '3'){
            $discussions = Discussion::join('teams', 'teams.id', 'discussions.entity_id')
            ->join('project_courses', 'project_courses.id', 'teams.project_course_id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->where('courses.semester_id', $semester_id)
            ->where('courses.faculty_id', Auth::user()->university_users->id)
            ->where('discussions.entity_type', 'team_discussion')->count();
        }else{
            $discussions = Discussion::join('teams', 'teams.id', 'discussions.entity_id')
            ->join('project_courses', 'project_courses.id', 'teams.project_course_id')
            ->join('courses', 'courses.id', 'project_courses.course_id')
            ->where('courses.semester_id', $semester_id)
            ->where('discussions.entity_type', 'team_discussion')->count();
        }
        return $discussions;
    }

    /*
    * Add file data
    */
    public function addFile($data) {

        $file = File::create($data);
        return $file;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discussion extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Define relation bewteen Discussion and DiscussionsComment model
     * @return object
     */
    public function comments() {
        return $this->hasMany(DiscussionsComment::class, 'discussion_id', 'id')
                    ->join('university_users', 'university_users.id', '=', 'discussions_comments.created_by')
                    ->join('users', 'users.id', '=', 'university_users.user_id')
                    ->select('discussions_comments.*', 'users.first_name','users.last_name' ,'users.profile_image')
                    ->orderBy('discussions_comments.created_at', 'asc');
    }

    /**
     * Define relation bewteen Discussion and File model
     * @return object
     */
    public function discussion_files() {
        return $this->hasOne(File::class, 'entity_id', 'id')
                    ->where('entity_type','discussion');
    }

    /**
     * Define relation bewteen Discussion and DiscussionLike model
     * @return object
     */
    public function likes() {
        return $this->hasMany(DiscussionLike::class, 'discussion_id', 'id');
    }

    /**
     * Define relation bewteen Discussion and Team model
     * @return object
     */
    public function teams() {
        return $this->belongsTo(Team::class, 'entity_id', 'id')
                    ->join('project_courses', 'project_courses.id', '=', 'teams.project_course_id')
                    ->join('projects', 'projects.id', '=', 'project_courses.project_id')
                    ->select('teams.*','projects.created_by as user_id');
    }

}

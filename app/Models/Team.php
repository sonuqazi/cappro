<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProjectCourse;
use App\Models\TeamStudent;
use App\Models\ProjectMilestone;
use Illuminate\Support\Facades\Config;
class Team extends Model
{
    use HasFactory;

    protected $table = 'teams';
    protected $guarded = [];

    /**
     * Define relation bewteen Team and ProjectCourse model
     * @return object
     */
    public function project_course_teams() {
        return $this->belongsTo(ProjectCourse::class, 'project_course_id', 'id');
    }

    /**
     * Define relation bewteen Team and TeamStudent model
     * @return object
     */
    public function team_students() {
        return $this->hasMany(TeamStudent::class, 'team_id', 'id')
        ->where('is_deleted', Config::get('constants.is_deleted.false'));
    }

    /**
     * Define relation bewteen Team and TeamStudent model
     * @return object
     */
    public function team_students_details() {
        return $this->hasMany(TeamStudent::class, 'team_id', 'id')
                    ->join('university_users', 'university_users.id', '=', 'team_students.student_id')
                    ->join('users', 'users.id', '=', 'university_users.user_id')
                    ->select('team_students.*','users.first_name','users.last_name','users.user_name','users.email','users.id as user_id','users.profile_image')
                    ->where('team_students.is_deleted', Config::get('constants.is_deleted.false'));
    }

    /**
     * Define relation bewteen Team and ProjectMilestone model
     * @return object
     */
    public function project_milestone() {
        return $this->hasMany(ProjectMilestone::class, 'team_id', 'id');
    }

    /**
     * Define relation bewteen Team and TeamStudentCount model
     * @return object
     */
    public function team_student_counts() {
        return $this->hasMany(TeamStudentCount::class, 'team_id', 'id');
    }
}

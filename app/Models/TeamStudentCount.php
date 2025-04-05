<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\ProjectCourse;
use App\Models\Team;

class TeamStudentCount extends Model
{
    use HasFactory;

    /**
     * Define relation ProjectCourse and TeamStudentCount
     * @return object
     */
    public function projectCourse() {
        return $this->hasMany(ProjectCourse::class, 'project_course_id', 'id');
    }

    /**
     * Define relation Courses and TeamStudentCount
     * @return object
     */
    public function courses() {
        return $this->hasMany(CourseStudent::class, 'course_id', 'course_id');
    }

    /**
     * Define relation Team and TeamStudentCount
     * @return object
     */
    public function teams() {
        return $this->hasMany(Team::class, 'id', 'team_id');
    }
}

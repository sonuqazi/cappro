<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Team;
use App\Models\Project;
use App\Models\Course;
use App\Models\ProjectCourseSetting;
use App\Models\CourseStudent;

class ProjectCourse extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamps = false;

    /**
     * Define relation bewteen ProjectCourse and Team model
     * @return object
     */
    public function teams() {
        return $this->hasMany(Team::class, 'project_course_id', 'id');
    }

    /**
     * Define relation bewteen ProjectCourse and Project model
     * @return object
     */
    public function projects() {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    /**
     * Define relation bewteen ProjectCourse and Course model
     * @return object
     */
    public function courses() {
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }

    /**
     * Define relation bewteen ProjectCourse and ProjectCourseSetting model
     * @return object
     */
    public function project_course_setting() {
        return $this->hasMany(ProjectCourseSetting::class, 'project_course_id', 'id');
    }

    /**
     * Define relation bewteen ProjectCourse and UniversityUser model
     * @return object
     */
    public function course_students() {
        return $this->belongsTo(CourseStudent::class, 'course_id', 'course_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProjectCourse;
use App\Models\UniversityUser;

class CourseStudent extends Model
{
    use HasFactory;

    /**
     * Define relation bewteen CourseStudent and ProjectCourse model
     * @return object
     */
    public function project_courses() {
        return $this->belongsTo(ProjectCourse::class, 'course_id', 'course_id');
    }

    /**
     * Define relation bewteen CourseStudent and  UniversityUser model
     * @return object
     */
    public function usercreated_by() {
        return $this->belongsTo(UniversityUser::class, 'student_id', 'id');
    }
}

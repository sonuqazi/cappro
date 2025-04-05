<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UniversityUser;
use App\Models\Semester;
use App\Models\PorjectCourse;

class Course extends Model
{
    use HasFactory;

    /**
     * Define relation bewteen Course and Semester model
     * @return object
     */
    public function semesters() {
        return $this->belongsTo(Semester::class, 'semester_id', 'id');
    }

    /**
     * Define relation bewteen Course and UniversityUser model for Faculty
     * @return object
     */
    public function faculty_data() {
        return $this->belongsTo(UniversityUser::class, 'faculty_id', 'id');
    }

    /**
     * Define relation bewteen Course and UniversityUser model for TA
     * @return object
     */
    public function ta_data() {
        return $this->belongsTo(UniversityUser::class, 'ta_id', 'id');
    }

    /**
     * Define relation bewteen Course and ProjectCourse model
     * @return object
     */
    public function project_course() {
        return $this->hasMany(ProjectCourse::class, 'course_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectCourseSetting extends Model
{
    use HasFactory;

    /**
     * Define relation bewteen ProjectCourseSetting and ProjectCourse model
     * @return object
     */
    public function project_course() {
        return $this->belongsTo(ProjectCourse::class, 'project_course_id', 'id');
    }
}

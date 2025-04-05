<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Evaluation;
use App\Models\ProjectCourse;
use App\Models\UniversityUser;

class EvaluationStart extends Model
{
    use HasFactory;
    protected $table = 'evaluation_starts';
    protected $fillable = ['evaluation_id','project_course_id','client_id','start_date','end_date','created_by','updated_by'];

    /**
     * Define relation bewteen EvaluationStart and Evaluation model
     * @return object
     */
    public function evaluations() {
        return $this->belongsTo(Evaluation::class, 'evaluation_id', 'id');
    }

    /**
     * Define relation bewteen EvaluationStart and ProjectCourse model
     * @return object
     */
    public function project_courses() {
        return $this->belongsTo(ProjectCourse::class, 'project_course_id', 'id');
    }

    /**
     * Define relation EvaluationStart and UniversityUser model started by user
     * @return object
     */
    public function started_by() {
        return $this->belongsTo(UniversityUser::class, 'created_by', 'id');
    }
}

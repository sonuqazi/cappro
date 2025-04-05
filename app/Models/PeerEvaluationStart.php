<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Team;
use App\Models\PeerEvaluation;
use App\Models\ProjectCourse;

class PeerEvaluationStart extends Model
{
    use HasFactory;

    /**
     * Define relation bewteen PeerEvaluationStart and Team model
     * @return object
     */
    public function teams() {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    /**
     * Define relation bewteen PeerEvaluationStart and PeerEvaluation model
     * @return object
     */
    public function peer_evaluations() {
        return $this->belongsTo(PeerEvaluation::class, 'peer_evaluation_id', 'id');
    }

    /**
     * Define relation bewteen PeerEvaluationStart and ProjectCourse model
     * @return object
     */
    public function project_courses() {
        return $this->belongsTo(ProjectCourse::class, 'project_course_id', 'id');
    }
}

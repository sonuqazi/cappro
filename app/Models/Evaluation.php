<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\EvaluationQuestion;
use App\Models\UniversityUser;

class Evaluation extends Model
{
    use HasFactory;

    /**
     * Define relation bewteen Evaluation and  EvaluationQuestion model
     * @return object
     */
    public function evaluation_question() {
        return $this->hasMany(EvaluationQuestion::class, 'evaluation_id', 'id');
    }

    /**
     * Define relation bewteen Evaluation and UniversityUser model Evaluation created by
     * @return object
     */
    public function usercreated_by() {
        return $this->belongsTo(UniversityUser::class, 'created_by', 'id');
    }

    /**
     * Define relation bewteen Evaluation and UniversityUser model Evaluation updated by
     * @return object
     */
    public function userupdated_by() {
        return $this->belongsTo(UniversityUser::class, 'updated_by', 'id');
    }
}

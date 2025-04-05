<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationQuestionStar extends Model
{
    use HasFactory;

    /**
     * Define relation bewteen EvaluationQuestionStar and UniversityUser model
     * @return object
     */
    public function created_by() {
        return $this->belongsTo(UniversityUser::class, 'rate_to', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PeerEvaluationRating;
use App\Models\UniversityUser;

class PeerEvaluation extends Model
{
    use HasFactory;

    /**
     * Define relation bewteen PeerEvaluation and PeerEvaluationRating model
     * @return object
     */
    public function peer_evaluation_rating() {
        return $this->hasMany(PeerEvaluationRating::class, 'peer_evaluation_id', 'id');
    }

    /**
     * Define relation bewteen PeerEvaluation and UniversityUser model PeerEvaluation created by
     * @return object
     */
    public function usercreated_by() {
        return $this->belongsTo(UniversityUser::class, 'created_by', 'id');
    }

    /**
     * Define relation bewteen PeerEvaluation and UniversityUser model PeerEvaluation updated by
     * @return object
     */
    public function userupdated_by() {
        return $this->belongsTo(UniversityUser::class, 'updated_by', 'id');
    }
}

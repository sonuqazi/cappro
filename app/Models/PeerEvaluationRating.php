<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PeerEvaluation;

class PeerEvaluationRating extends Model
{
    use HasFactory;

    /**
     * Define relation bewteen PeerEvaluationRating and PeerEvaluation model
     * @return object
     */
    public function peer_evaluation_rating() {
        return $this->belongsTo(PeerEvaluation::class, 'peer_evaluation_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UniversityUser;

class PeerEvaluationRatingStar extends Model
{
    use HasFactory;

    /**
     * Define relation bewteen PeerEvaluationRatingStar and UniversityUser model rated to user
     * @return object
     */
    public function rated_to_user() {
        return $this->belongsTo(UniversityUser::class, 'rate_to', 'id');
    }

    /**
     * Define relation bewteen PeerEvaluationRatingStar and UniversityUser model rated by user
     * @return object
     */
    public function rated_by_user() {
        return $this->belongsTo(UniversityUser::class, 'created_by', 'id');
    }
}

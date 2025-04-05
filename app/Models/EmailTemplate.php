<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UniversityUser;

class EmailTemplate extends Model
{
    use HasFactory;

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

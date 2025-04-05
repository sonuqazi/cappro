<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Milestone;
use App\Models\UniversityUser;

class PmPlan extends Model
{
    use HasFactory;

    /**
     * Define relation Milestone and Pm Plan
     * @return object
     */
    public function milestones() {
        return $this->hasMany(Milestone::class, 'plan_id', 'id');
    }

    /**
     * Define relation bewteen PmPlan and UniversityUser model pm_plans created by
     * @return object
     */
    public function usercreated_by() {
        return $this->belongsTo(UniversityUser::class, 'created_by', 'id');
    }

    /**
     * Define relation bewteen PmPlan and UniversityUser model pm_plans updated by
     * @return object
     */
    public function userupdated_by() {
        return $this->belongsTo(UniversityUser::class, 'updated_by', 'id');
    }
}

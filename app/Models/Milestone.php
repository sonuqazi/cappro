<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\File;
use App\Models\MilestoneProgress;
use App\Models\ProjectMilestone;
use App\Models\PmPlan;
class Milestone extends Model
{
    use HasFactory;

    /**
     * Define relation Milestone and File model
     * @return object
     */
    public function milestone_files() {
        return $this->hasMany(File::class, 'entity_id', 'id');
    }

    /**
     * Define relation Milestone and MilestoneProgress model
     * @return object
     */
    public function milestone_progress() {
        return $this->hasMany(MilestoneProgress::class, 'milestone_id', 'id');
    }

    /**
     * Define relation Milestone and ProjectMilestone model
     * @return object
     */
    public function project_milestone() {
        return $this->hasMany(ProjectMilestone::class, 'milestone_id', 'id');
    }

    /**
     * Define relation Milestone and PmPlan model
     * @return object
     */
    public function project_plan() {
        return $this->hasMany(PmPlan::class, 'id', 'plan_id');
    }
}

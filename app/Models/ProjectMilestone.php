<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Team;
use App\Models\Milestone;

class ProjectMilestone extends Model
{
    use HasFactory;
    protected $table = 'project_milestones';
    /**
     * Get the user that owns the ProjectMilestone
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function teams() {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }  

    /**
     * Get the milestone
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function milestones() {
        return $this->belongsTo(Milestone::class, 'milestone_id', 'id');
    }  
    
}

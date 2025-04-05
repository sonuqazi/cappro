<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MilestoneProgress extends Model
{
    use HasFactory;
    protected $table = 'milestone_progresses';

    /**
     * Define relation MilestoneProgress and UniversityUser model created by user
     * @return object
     */
    public function completed_by() {
        return $this->belongsTo(UniversityUser::class, 'created_by', 'id');
    }
}

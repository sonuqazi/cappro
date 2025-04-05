<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Project;
use App\Models\UniversityUser;

class ProjectRequest extends Model
{
    use HasFactory;

    /**
     * Define relation bewteen ProjectRequest and Project model
     * @return object
     */
    public function projects() {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    /**
     * Define relation bewteen ProjectRequest and UniversityUser model
     * @return object
     */
    public function users() {
        return $this->belongsTo(UniversityUser::class, 'created_by', 'id');
    }
}

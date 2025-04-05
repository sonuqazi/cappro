<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\ProjectCourse;
use App\Models\ChangeRequest;
use App\Models\TeamTask;
use App\Models\UniversityUser;
use App\Models\File;

class Task extends Model
{
    use HasFactory;

    /**
     * Define relation bewteen Task and ProjectCourse model
     * @return object
     */
    public function project_courses()
    {
        return $this->belongsTo(ProjectCourse::class);
    }

    /**
     * Define relation bewteen Task and ChangeRequest model
     * @return object
     */
    public function change_request()
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    /**
     * Define relation bewteen Task and UniversityUser model created by
     * @return object
     */
    public function created_by_user()
    {
        return $this->belongsTo(UniversityUser::class, 'created_by', 'id');
    }

    /**
     * Define relation bewteen Task and UniversityUser model updated by
     * @return object
     */
    public function updeted_by_user()
    {
        return $this->belongsTo(UniversityUser::class, 'updated_by', 'id');
    }

    /**
     * Define relation bewteen Task and TeamTask model
     * @return object
     */
    public function team_task()
    {
        return $this->hasMany(TeamTask::class);
    }

    /**
     * Define relation bewteen Task and File model
     * @return object
     */
    public function task_files()
    {
        return $this->belongsTo(File::class, 'id', 'entity_id')
            ->join('tasks', 'tasks.id', '=', 'files.entity_id')
            ->where('files.entity_type', '=', 'task_file');
    }
}

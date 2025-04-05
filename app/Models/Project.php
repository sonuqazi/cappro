<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProjectCourse;
use App\Models\File;
use App\Models\ProjectCategory;
use App\Models\UniversityUser;

class Project extends Model {
    use HasFactory;

    protected $table = 'projects';
    protected $fillable = [
        // 'user_id',
        'title',
        'categories_id',
		'description',
		'background',
		'justification',
		'deliverable',
		'status',
		//'approved',
		'created_by',
        'client_id',
		'updated_by',
		'created_at',
		'updated_at',
    ];

    /**
     * Define relation bewteen Project and Categories model
     * @return object
     */
    public function categories() {
        return $this->belongsTo(Categories::class, 'categories_id', 'id');
    }

    /**
     * Define relation bewteen Project and UniversityUser model project created by
     * @return object
     */
    public function usercreated_by() {
        return $this->belongsTo(UniversityUser::class, 'created_by', 'id');
    }

    /**
     * Define relation bewteen Project and UniversityUser model project updated by
     * @return object
     */
    public function userupdated_by() {
        return $this->belongsTo(UniversityUser::class, 'updated_by', 'id');
    }

    /**
     * Define relation bewteen Project and UniversityUser model for project client
     * @return object
     */
    public function project_client() {
        return $this->belongsTo(UniversityUser::class, 'client_id', 'id');
    }

    /**
     * Define relation bewteen Project and ProjectCourse model
     * @return object
     */
    public function project_course() {
        return $this->hasMany(ProjectCourse::class, 'project_id', 'id');
    }

    /**
     * Define relation bewteen Project and File model
     * @return object
     */
    public function project_files() {
        return $this->hasMany(File::class, 'entity_id', 'id');
    }

    /**
     * Define relation bewteen Project and ProjectCategory model
     * @return object
     */
    public function project_categories() {
        return $this->hasMany(ProjectCategory::class, 'project_id', 'id')
                    ->leftJoin('categories', 'categories.id', '=', 'project_categories.category_id')
                    ->select('project_categories.*','categories.title');
    }

    /**
     * Define relation bewteen Project and ProjectRequest model
     * @return object
     */
    public function project_requests() {
        return $this->hasMany(ProjectRequest::class, 'project_id', 'id');
    }

    /**
     * Define relation bewteen Project and UniversityUser model project client
     * @return object
     */
    public function client() {
        return $this->belongsTo(UniversityUser::class, 'client_id', 'id');
    }
}

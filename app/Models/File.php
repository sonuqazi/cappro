<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Project;

class File extends Model
{
    use HasFactory;
    protected $table = 'files';
    protected $fillable = [
		'entity_id',
		'name',
		'entity_type',
		'location',
		'description',
        'mime_type',
		'created_by',
		'created_at',
		'updated_at',
        'is_visibleToStudents',
    ];


    /**
     * Define relation bewteen File and Project model
     * @return object
     */
    public function file_project() {
        return $this->belongsTo(Project::class, 'entity_id', 'id');
    }

    /**
     * Define relation bewteen File and User model
     * @return object
     */
    // public function usercreated_by() {
    //     return $this->belongsTo(User::class, 'created_by', 'id');
    // }

    /**
     * Define relation bewteen File and User model
     * @return object
     */
    public function mediaFileCreated_by() {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}

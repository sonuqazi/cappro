<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Team;
use App\Models\UniversityUser;

class TeamStudent extends Model
{
    use HasFactory;

    protected $table = 'team_students';
    protected $guarded = [];

    /**
     * Define relation bewteen TeamStudent and Team model
     * @return object
     */
    public function students_team() {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    /**
     * Define relation bewteen TeamStudent and University Users model
     * @return object
     */
    public function university_students() {
        return $this->belongsTo(UniversityUser::class, 'student_id', 'id');
    }
}

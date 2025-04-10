<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Define relation bewteen Semester and Course model
     * @return object
     */
     public function courses() {
        return $this->hasMany(Course::class, 'semester_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UniversityUser extends Model
{
    use HasFactory;

    protected $table = 'university_users';
    protected $guarded = [];

    /**
     * Define relation bewteen UniversityUser and User model
     * @return object
     */
    public function university_users() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

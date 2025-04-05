<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Organization;

class UserProfile extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Define relation bewteen UserProfile and User model
     * @return object
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Define relation bewteen UserProfile and Organization model
     * @return object
     */
    public function organizations()
    {
        return $this->belongsTo(Organization::class, 'org_id', 'id');
    }
}

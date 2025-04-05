<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use App\Models\UserProfile;
use App\Models\UniversityUser;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'first_name',
        'last_name',
        'user_name',
        'email',
        'password',
        'role_id',
        'department',
        'status',
        'email_verification_token',
        'created_by',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Define relation bewteen User and UserProfile model
     * @return object
     */
    public function user_profiles()
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }
    
    /**
     * Define relation bewteen User and UniversityUser model
     * @return object
     */
    public function university_users()
    {
        return $this->hasOne(UniversityUser::class);
    }

    /**
     * Update user password
     * @return object
     */
    function update_password($password, $id, $updatedAt)
    {
        $user = User::find($id);
        $user->password = Hash::make($password);
        $user->updated_at = $updatedAt;
        $user->save();
        return $user;
    }    
    
    /**
     * Define relation bewteen User and UserNotification model
     * @return object
     */
    public function notification() {
        return $this->hasMany(UserNotification::class, 'user_id', 'id');
    }

    /**
     * Define relation bewteen User and Project model created by
     * @return object
     */
    public function userprojectscreated_by() {
        return $this->hasMany(Project::class, 'created_by', 'id');
    }

    /**
     * Define relation bewteen User and Project model updated by
     * @return object
     */
    public function userprojectsupdated_by() {
        return $this->hasMany(Project::class, 'updated_by', 'id');
    }
}

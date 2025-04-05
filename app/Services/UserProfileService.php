<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UniversityUser;

class UserProfileService
{

    /**
     * Get user profile details using user ID.
     * @param Int $user_id
     * @return Object
     */
    public function getUserProfile($user_id) {
        try {
            $user_profile_data = User::where('id',$user_id)->with('user_profiles', 'university_users')->first();
        return $user_profile_data;
        }  catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get user profile ID using user ID.
     * @param Int $user_id
     * @return Object|Boolean
     */
    public function getUserProfileID($user_id) {
        try {
            $user_id = UserProfile::where('user_id', $user_id)->first();
        return $user_id->id;
        }  catch (Throwable $e) {            
            return false;
        }
        
    }


    /**
     * Get user university Id
     * @param Int $userId
     * @return Object
     */
    public function getUserUniversityID($userId = null)
    {
        $universityUser =  UniversityUser::select('id')
            ->Where('user_id', $userId)->first();
        return $universityUser;
    }

    
    /**
     * Get user profile by university user
     * @param Int $userId
     * @return Object
     */
    public function getUserProfileByUniversityUser($user_id)
    {
        $user_profile_data = UniversityUser::where('id',$user_id)->with('university_users')->first();
        return $user_profile_data;
    }
}

<?php

namespace App\Traits;
use App\Models\Team;

trait TeamsTrait {

    public function getProjectTeams($project_course_id, $a='', $b='', $isDeleted=2) {
       
        $teams = Team::with('project_course_teams')
                ->whereHas('project_course_teams', function($q) use($project_course_id) {
                 // Query the name field in status table
                $q->where('project_course_id', '=', $project_course_id); // '=' is optional
                })->get();
        return $teams;
    }

    public function getUsersTeams($user_id) {
        
         $teams = Team::with('project_course_teams')
                ->leftJoin('projects AS t4', 't4.id', '=', 't3.project_id')
                ->leftJoin('users AS t5', 't5.id', '=', 't4.created_by')
                ->where('t4.created_by', '=', $user_id)->get();
         return $teams;
     }
}
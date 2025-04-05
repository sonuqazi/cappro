<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TimeAccount;
use App\Models\UniversityUser;
use App\Models\Team;
class TimeSpend extends Model
{
    use HasFactory;
    protected $table = 'time_spends';
    protected $fillable = ['title','description','time_category_id','time',
    'team_id','created_by','status'];
  
    /**
     * Define relation bewteen TimeSpend and TimeAccount model
     * @return object
     */
    public function timecategory() {
        return $this->hasMany(TimeAccount::class, 'id', 'time_category_id');
    }

    /**
     * Define relation bewteen TimeSpend and UniversityUser model TimeSpend created by
     * @return object
     */
    public function usercreated_by() {
        return $this->belongsTo(UniversityUser::class, 'created_by', 'id');
    }

    /**
     * Define relation bewteen TimeSpend and UniversityUser model TimeSpend updated by
     * @return object
     */
    public function userupdated_by() {
        return $this->belongsTo(UniversityUser::class, 'updated_by', 'id');
    }

    /**
     * Define relation TimeSpend and Team model
     * @return object
     */
    public function teams() {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }
}

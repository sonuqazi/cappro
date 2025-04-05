<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\MoneyCategory;
use App\Models\UniversityUser;
use App\Models\Team;
class MoneySpend extends Model
{
    use HasFactory;
    protected $table = 'money_spends';
    protected $fillable = ['title','description','money_account_id','money_category_id','amount',
    'team_id','created_by','status'];
  
    /**
     * Define relation MoneySpend and MoneyCategory model
     * @return object
     */
    public function moneycategory() {
        return $this->hasMany(MoneyCategory::class, 'id', 'money_category_id');
    }

    /**
     * Define relation bewteen MoneySpend and UniversityUser model MoneySpend created by
     * @return object
     */
    public function usercreated_by() {
        return $this->belongsTo(UniversityUser::class, 'created_by', 'id');
    }

    /**
     * Define relation bewteen MoneySpend and UniversityUser model MoneySpend updated by
     * @return object
     */
    public function userupdated_by() {
        return $this->belongsTo(UniversityUser::class, 'updated_by', 'id');
    }

    /**
     * Define relation MoneySpend and Team model
     * @return object
     */
    public function teams() {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UniversityUser;

class MoneyCategory extends Model
{
    use HasFactory;
    protected $table = 'money_categories';

    /**
     * Define relation bewteen MoneyCategory and UniversityUser model money_categories created by
     * @return object
     */
    public function usercreated_by() {
        return $this->belongsTo(UniversityUser::class, 'created_by', 'id');
    }

    /**
     * Define relation bewteen MoneyCategory and UniversityUser model money_categories updated by
     * @return object
     */
    public function userupdated_by() {
        return $this->belongsTo(UniversityUser::class, 'updated_by', 'id');
    }
}

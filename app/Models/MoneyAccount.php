<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UniversityUser;

class MoneyAccount extends Model
{
    use HasFactory;
    protected $table = 'money_accounts';

    /**
     * Define relation bewteen MoneyAccount and UniversityUser model money_accounts created by
     * @return object
     */
    public function usercreated_by() {
        return $this->belongsTo(UniversityUser::class, 'created_by', 'id');
    }

    /**
     * Define relation bewteen MoneyAccount and UniversityUser model money_accounts updated by
     * @return object
     */
    public function userupdated_by() {
        return $this->belongsTo(UniversityUser::class, 'updated_by', 'id');
    }
}

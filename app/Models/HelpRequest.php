<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Define relation bewteen DiscussionsComment and UniversityUser model
     * @return object
     */
    public function created_by() {
        return $this->belongsTo(UniversityUser::class, 'created_by', 'id');
    }
}

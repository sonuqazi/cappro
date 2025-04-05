<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscussionLike extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    /**
     * Define relation bewteen DiscussionLike and UniversityUser model
     * @return object
     */
    public function user()
    {
        return $this->belongsTo(UniversityUser::class)->withDefault();
    }

}

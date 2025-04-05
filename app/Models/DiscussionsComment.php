<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscussionsComment extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Define relation bewteen DiscussionsComment and UniversityUser model
     * @return object
     */
    public function commented_by() {
        return $this->belongsTo(UniversityUser::class, 'created_by', 'id');
    }

    /**
     * Define relation bewteen DiscussionsComments and DiscussionsCommentLike model
     * @return object
     */
    public function commentLikes() {
        return $this->hasMany(DiscussionsCommentLike::class, 'discussions_comment_id', 'id');
    }
}

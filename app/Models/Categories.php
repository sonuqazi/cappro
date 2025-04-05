<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{
    use HasFactory;
    protected $table = 'categories';
    protected $fillable = ['title', 'created_by'];

    /**
     * Relationship between Categories and Project model    
     * @return object
     */
    public function projects() {
        return $this->hasMany(Project::class, 'categories_id', 'id');
    }
}


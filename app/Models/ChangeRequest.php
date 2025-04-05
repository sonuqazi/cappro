<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChangeRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Relationship between ChangeRequest and Task model    
     * @return object
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}

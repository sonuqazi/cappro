<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    /**
     * Define relation Notification and UserNotification model
     * @return object
     */
    public function userNotification() {
        return $this->hasMany(UserNotification::class, 'notification_id', 'id');
    }
}




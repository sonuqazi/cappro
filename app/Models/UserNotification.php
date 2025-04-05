<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'user_notifications';


    /**
     * Define relation bewteen UserNotification and Notification model
     * @return object
     */
    public function notification() {
        return $this->belongsTo(Notification::class, 'notification_id', 'id');
    }

    /**
     * Define relation bewteen UserNotification and User model
     * @return object
     */
    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use Illuminate\Database\Eloquent\Model;

class AppNotificationRead extends Model
{
    use HasUniqueId;

    public $timestamps = false;

    protected $fillable = ['app_notification_id', 'user_id', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];
}

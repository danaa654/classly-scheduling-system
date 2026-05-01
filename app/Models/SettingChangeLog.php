<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingChangeLog extends Model
{
    protected $fillable = [
        'user_id', 'setting_key', 'old_value', 'new_value',
        'action', 'ip_address', 'change_reason', 'changed_at'
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
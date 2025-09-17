<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AuthToken extends Model
{
    protected $table = 'auth_tokens';
    
    protected $fillable = [
        'phone',
        'token',
        'role',
        'expires_at',
        'used_at'
    ];
    
    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function isExpired()
    {
        return Carbon::parse($this->expires_at)->isPast();
    }

    public function isUsed()
    {
        return !is_null($this->used_at);
    }

    public function markAsUsed()
    {
        $this->used_at = Carbon::now();
        $this->save();
    }

    public static function generateToken()
    {
        return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
}
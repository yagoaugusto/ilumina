<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'team_id',
        'phone',
        'is_active'
    ];
    
    protected $hidden = [
        'password'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // Relationships
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public static function findByPhone($phone)
    {
        return static::where('phone', $phone)->first();
    }

    public static function createCitizen($phone, $name = null)
    {
        return static::create([
            'phone' => $phone,
            'name' => $name,
            'role' => 'citizen',
            'is_active' => true
        ]);
    }
}
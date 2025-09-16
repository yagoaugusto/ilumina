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
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $table = 'teams';
    
    protected $fillable = [
        'name',
        'description',
        'is_active'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // Relationships
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_team_id');
    }
    
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
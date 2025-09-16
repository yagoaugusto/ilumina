<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'tickets';
    
    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'latitude',
        'longitude',
        'address',
        'photo_url',
        'citizen_phone',
        'citizen_name',
        'assigned_team_id',
        'created_at',
        'updated_at',
        'due_date'
    ];
    
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'due_date' => 'datetime'
    ];
    
    // Relationships
    public function team()
    {
        return $this->belongsTo(Team::class, 'assigned_team_id');
    }
    
    public function comments()
    {
        return $this->hasMany(TicketComment::class);
    }
}
<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Team;

class TicketService
{
    public function createTicket($data)
    {
        // Calculate SLA due date based on priority
        $dueDays = $this->getSLADays($data['priority'] ?? 'medium');
        $data['due_date'] = date('Y-m-d H:i:s', strtotime("+{$dueDays} days"));
        
        // Auto-assign to available team if not specified
        if (!isset($data['assigned_team_id'])) {
            $availableTeam = Team::where('is_active', true)->first();
            if ($availableTeam) {
                $data['assigned_team_id'] = $availableTeam->id;
            }
        }
        
        return Ticket::create($data);
    }
    
    public function getKPIs()
    {
        return [
            'total_tickets' => Ticket::count(),
            'open_tickets' => Ticket::where('status', 'open')->count(),
            'in_progress_tickets' => Ticket::where('status', 'in_progress')->count(),
            'closed_tickets' => Ticket::where('status', 'closed')->count(),
            'overdue_tickets' => Ticket::where('due_date', '<', now())
                                     ->whereNotIn('status', ['closed', 'resolved'])
                                     ->count(),
            'avg_resolution_time' => $this->getAverageResolutionTime()
        ];
    }
    
    private function getSLADays($priority)
    {
        switch ($priority) {
            case 'high':
                return 1;
            case 'medium':
                return 3;
            case 'low':
                return 7;
            default:
                return 3;
        }
    }
    
    private function getAverageResolutionTime()
    {
        // Calculate average resolution time in hours
        $resolvedTickets = Ticket::whereIn('status', ['closed', 'resolved'])->get();
        
        if ($resolvedTickets->isEmpty()) {
            return 0;
        }
        
        $totalHours = 0;
        foreach ($resolvedTickets as $ticket) {
            $created = new \DateTime($ticket->created_at);
            $updated = new \DateTime($ticket->updated_at);
            $diff = $created->diff($updated);
            $totalHours += ($diff->days * 24) + $diff->h;
        }
        
        return round($totalHours / $resolvedTickets->count(), 2);
    }
}
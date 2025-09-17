<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

// API v1 group
$app->group('/api/v1', function ($group) {
    // Authentication endpoints
    $group->post('/auth/request-link', '\App\Controllers\AuthController:requestLink');
    $group->post('/auth/confirm', '\App\Controllers\AuthController:confirm');
    
    // Tickets endpoints
    $group->get('/tickets', '\App\Controllers\TicketController:index');
    $group->post('/tickets', '\App\Controllers\TicketController:store');
    $group->get('/tickets/{id}', '\App\Controllers\TicketController:show');
    $group->put('/tickets/{id}', '\App\Controllers\TicketController:update');
    $group->delete('/tickets/{id}', '\App\Controllers\TicketController:delete');
    
    // Teams endpoints
    $group->get('/teams', '\App\Controllers\TeamController:index');
    $group->post('/teams', '\App\Controllers\TeamController:store');
    
    // Users endpoints
    $group->get('/users', '\App\Controllers\UserController:index');
    $group->post('/users', '\App\Controllers\UserController:store');
    
    // KPIs endpoint
    $group->get('/kpis', function (Request $request, Response $response, $args) {
        $kpis = [
            'total_tickets' => 150,
            'open_tickets' => 25,
            'in_progress_tickets' => 18,
            'closed_tickets' => 107,
            'overdue_tickets' => 5,
            'avg_resolution_time' => 24.5
        ];
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $kpis
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    });
});
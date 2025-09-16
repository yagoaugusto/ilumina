<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Team;

class TeamController
{
    public function index(Request $request, Response $response, $args)
    {
        $teams = Team::with('users')->get();
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $teams
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function store(Request $request, Response $response, $args)
    {
        $data = json_decode($request->getBody()->getContents(), true);
        
        $team = Team::create($data);
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $team,
            'message' => 'Team created successfully'
        ]));
        
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
}
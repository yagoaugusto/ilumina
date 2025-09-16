<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Ticket;

class TicketController
{
    public function index(Request $request, Response $response, $args)
    {
        $tickets = Ticket::with('team')->get();
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $tickets
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function store(Request $request, Response $response, $args)
    {
        $data = json_decode($request->getBody()->getContents(), true);
        
        $ticket = Ticket::create($data);
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $ticket,
            'message' => 'Ticket created successfully'
        ]));
        
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
    
    public function show(Request $request, Response $response, $args)
    {
        $id = $args['id'];
        $ticket = Ticket::with('team')->find($id);
        
        if (!$ticket) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Ticket not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $ticket
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function update(Request $request, Response $response, $args)
    {
        $id = $args['id'];
        $data = json_decode($request->getBody()->getContents(), true);
        
        $ticket = Ticket::find($id);
        
        if (!$ticket) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Ticket not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        $ticket->update($data);
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $ticket,
            'message' => 'Ticket updated successfully'
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function delete(Request $request, Response $response, $args)
    {
        $id = $args['id'];
        $ticket = Ticket::find($id);
        
        if (!$ticket) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Ticket not found'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        $ticket->delete();
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Ticket deleted successfully'
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
}
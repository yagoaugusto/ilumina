<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;

class UserController
{
    public function index(Request $request, Response $response, $args)
    {
        $users = User::with('team')->get();
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $users
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function store(Request $request, Response $response, $args)
    {
        $data = json_decode($request->getBody()->getContents(), true);
        
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $user = User::create($data);
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $user,
            'message' => 'User created successfully'
        ]));
        
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
}
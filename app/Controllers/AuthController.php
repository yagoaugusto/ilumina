<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;
use App\Models\AuthToken;
use App\Services\WhatsAppService;
use Carbon\Carbon;

class AuthController
{
    private $whatsAppService;

    public function __construct()
    {
        $this->whatsAppService = new WhatsAppService();
    }

    public function requestLink(Request $request, Response $response, $args)
    {
        $data = json_decode($request->getBody()->getContents(), true);
        
        // Validate input
        if (empty($data['phone'])) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Telefone é obrigatório'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }

        $phone = $this->normalizePhone($data['phone']);
        $role = $data['role'] ?? 'citizen';

        // Validate role
        if (!in_array($role, ['citizen', 'manager'])) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Tipo de usuário inválido'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }

        // For managers, check if user exists with manager role
        if ($role === 'manager') {
            $user = User::findByPhone($phone);
            if (!$user || !in_array($user->role, ['manager', 'admin'])) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'Usuário não autorizado como gestor'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
        }

        try {
            // Clean old unused tokens for this phone
            AuthToken::where('phone', $phone)
                ->where('used_at', null)
                ->delete();

            // Generate new token
            $token = AuthToken::generateToken();
            $expiresAt = Carbon::now()->addMinutes(10);

            // Save token
            AuthToken::create([
                'phone' => $phone,
                'token' => $token,
                'role' => $role,
                'expires_at' => $expiresAt
            ]);

            // Send WhatsApp message
            $result = $this->whatsAppService->sendVerificationCode($phone, $token, $role);
            
            if (!$result['success']) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'Erro ao enviar código via WhatsApp: ' . ($result['error'] ?? 'Erro desconhecido')
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'Código enviado via WhatsApp',
                'expires_in' => 600 // 10 minutes in seconds
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (\Exception $e) {
            error_log('Auth request error: ' . $e->getMessage());
            
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Erro interno do servidor'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function confirm(Request $request, Response $response, $args)
    {
        $data = json_decode($request->getBody()->getContents(), true);
        
        // Validate input
        if (empty($data['phone']) || empty($data['token'])) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Telefone e código são obrigatórios'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }

        $phone = $this->normalizePhone($data['phone']);
        $token = $data['token'];
        $name = $data['name'] ?? null;

        try {
            // Find valid token
            $authToken = AuthToken::where('phone', $phone)
                ->where('token', $token)
                ->where('used_at', null)
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if (!$authToken) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'Código inválido ou expirado'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

            // Mark token as used
            $authToken->markAsUsed();

            // Find or create user
            $user = User::findByPhone($phone);
            
            if (!$user && $authToken->role === 'citizen') {
                // Create new citizen user
                $user = User::createCitizen($phone, $name);
            } elseif (!$user) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'Usuário não encontrado'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Update name if provided
            if ($name && $user->role === 'citizen') {
                $user->name = $name;
                $user->save();
            }

            // Generate access token (simple JWT-like token)
            $accessToken = $this->generateAccessToken($user);

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'Login realizado com sucesso',
                'access_token' => $accessToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'role' => $user->role
                ]
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (\Exception $e) {
            error_log('Auth confirm error: ' . $e->getMessage());
            
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Erro interno do servidor'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    private function normalizePhone($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present
        if (!str_starts_with($phone, '55')) {
            $phone = '55' . $phone;
        }
        
        return $phone;
    }

    private function generateAccessToken($user)
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'role' => $user->role,
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ]);

        $headerEncoded = $this->base64UrlEncode($header);
        $payloadEncoded = $this->base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, 'ilumina_secret_key', true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
<?php

namespace App\Services;

class WhatsAppService
{
    private $instanceId;
    private $token;
    private $baseUrl;

    public function __construct()
    {
        $this->instanceId = $_ENV['ULTRAMSG_INSTANCE_ID'] ?? null;
        $this->token = $_ENV['ULTRAMSG_TOKEN'] ?? null;
        $this->baseUrl = 'https://api.ultramsg.com';
    }

    public function sendMessage($phone, $message)
    {
        if (!$this->instanceId || !$this->token) {
            error_log('UltraMsg credentials not configured');
            return ['success' => false, 'error' => 'WhatsApp service not configured'];
        }

        $url = $this->baseUrl . '/' . $this->instanceId . '/messages/chat';
        
        $data = [
            'token' => $this->token,
            'to' => $this->formatPhone($phone),
            'body' => $message
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log('UltraMsg cURL error: ' . $error);
            return ['success' => false, 'error' => 'Failed to send message'];
        }

        $responseData = json_decode($response, true);
        
        if ($httpCode === 200 && isset($responseData['sent']) && $responseData['sent']) {
            return ['success' => true];
        } else {
            error_log('UltraMsg API error: ' . $response);
            return ['success' => false, 'error' => 'Failed to send WhatsApp message'];
        }
    }

    public function sendVerificationCode($phone, $code, $role)
    {
        $message = "🔐 *Ilumina - Código de Verificação*\n\n";
        $message .= "Seu código de acesso ";
        $message .= ($role === 'manager') ? "(Gestor)" : "(Cidadão)";
        $message .= " é: *{$code}*\n\n";
        $message .= "⏰ Válido por 10 minutos\n";
        $message .= "🔒 Não compartilhe este código";

        return $this->sendMessage($phone, $message);
    }

    private function formatPhone($phone)
    {
        // Remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present
        if (!str_starts_with($phone, '55')) {
            $phone = '55' . $phone;
        }
        
        return $phone . '@c.us';
    }
}
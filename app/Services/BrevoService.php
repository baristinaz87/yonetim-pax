<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class BrevoService
{
    private Client $client;
    private string $senderNumber;

    public function __construct()
    {
        $this->senderNumber = env('BREVO_SENDER_NUMBER');
        $token = env('BREVO_TOKEN');
        $url = env('BREVO_URL');
        $this->client = new Client([
            "base_uri" => $url,
            'headers' => ['api-key' => $token, 'Accept' => 'application/json'],
        ]);
    }

    public function sendTemplateMessage(array $phones, string $templateId): array
    {
        return $this->sendMessage($phones, ["templateId" => intval($templateId)]);
    }

    public function sendCustomMessage(array $phones, string $message): array
    {
        return $this->sendMessage($phones, ["text" => $message]);
    }

    private function sendMessage(array $phones, array $values): array
    {
        try {
            $constantValues = ["contactNumbers" => $phones, "senderNumber" => $this->senderNumber];
            $response = $this->client
                ->post("/v3/whatsapp/sendMessage", ["json" => array_merge($values, $constantValues)]);
            $content = $response->getBody()->getContents();
            return json_decode($content, true);
        } catch (GuzzleException $e) {
            // TODO
            dd($e->getMessage());
        }
    }
}

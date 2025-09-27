<?php

namespace App\Services;

use App\Models\EmailContent;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class BrevoService
{
    private Client $client;
    private string $senderNumber;
    private string $senderEmailName;
    private string $senderEmail;

    public function __construct()
    {
        $this->senderEmailName = env('BREVO_SENDER_EMAIL_NAME');
        $this->senderEmail = env('BREVO_SENDER_EMAIL');
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

    public function sendTemplateEmail(string $toName, array $emails, string $templateId): array
    {
        $emailContent = EmailContent::find($templateId);
        if (!$emailContent instanceof EmailContent) {
            // TODO
            dd($templateId." id'li email şablonu bulunamadı.");
        }

        return $this->sendEmail($toName, $emails, $emailContent->subject, $emailContent->content);
    }

    private function sendEmail(string $toName, array $emails, string $subject, string $content): array
    {
        try {
            $payload = [
                "subject" => $subject,
                "htmlContent" => $content,
                "sender" => ["name" => $this->senderEmailName, "email" => $this->senderEmail],
                "to" => array_map(function ($email) use ($toName) {
                    return ["email" => $email, "name" => $toName];
                }, $emails),
            ];

            $response = $this->client->post("/v3/smtp/email", ["json" => $payload]);
            $content = $response->getBody()->getContents();
            return json_decode($content, true);
        } catch (GuzzleException $e) {
            // TODO
            dd($e->getMessage());
        }
    }
}

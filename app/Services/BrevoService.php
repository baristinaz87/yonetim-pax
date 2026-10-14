<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EmailContent;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

/**
 *
 */
class BrevoService
{
    private Client $client;
    private string $senderNumber;
    private string $senderEmailName;
    private string $senderEmail;

    public function __construct()
    {
        $this->senderEmailName = config('services.brevo.sender_email_name');
        $this->senderEmail = config('services.brevo.sender_email');
        $this->senderNumber = config('services.brevo.sender_number');
        $token = config('services.brevo.token');
        $url = config('services.brevo.url');
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
            throw new RuntimeException($e->getMessage(), previous: $e);
        }
    }

    public function sendTemplateEmail(string $toName, array $emails, $templateId): array
    {
        $emailContent = EmailContent::find($templateId);
        if (!$emailContent instanceof EmailContent) {
            throw new RuntimeException($templateId." id'li email şablonu bulunamadı.");
        }

        if ($emailContent->brevo_template_id !== null) {
            return $this->sendBrevoTemplateEmail($toName, $emails, $emailContent->brevo_template_id);
        }

        if ($emailContent->subject === null || $emailContent->content === null) {
            throw new RuntimeException('Veritabanı şablonunda konu ve içerik zorunludur.');
        }

        return $this->sendEmail($toName, $emails, $emailContent->subject, $emailContent->content);
    }

    private function sendBrevoTemplateEmail(string $toName, array $emails, int $brevoId): array
    {
        try {
            $payload = [
                'templateId' => $brevoId,
                'sender' => ['name' => $this->senderEmailName, 'email' => $this->senderEmail],
                'to' => $this->recipients($toName, $emails),
            ];

            $response = $this->client->post('/v3/smtp/email', ['json' => $payload]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new RuntimeException($e->getMessage(), previous: $e);
        }
    }

    private function sendEmail(string $toName, array $emails, string $subject, string $content): array
    {
        try {
            $payload = [
                "subject" => $subject,
                "htmlContent" => $content,
                "sender" => ["name" => $this->senderEmailName, "email" => $this->senderEmail],
                "to" => $this->recipients($toName, $emails),
            ];

            $response = $this->client->post("/v3/smtp/email", ["json" => $payload]);
            $content = $response->getBody()->getContents();
            return json_decode($content, true);
        } catch (GuzzleException $e) {
            throw new RuntimeException($e->getMessage(), previous: $e);
        }
    }

    /**
     * WhatsApp numarasına bağlı Brevo kişisinin özniteliklerini günceller;
     * kişi yoksa (404) aynı özniteliklerle oluşturur.
     *
     * Şablon gönderimi kişinin var olmasını gerektirmez; öznitelikler yalnızca
     * şablondaki yer tutucuların (SHOP_NAME, CREDIT vb.) dolması için yazılır.
     *
     * @param array<string, mixed> $attributes  Brevo'da tanımlı, büyük harfli öznitelik adları
     */
    public function upsertWhatsappContactAttributes(string $whatsapp, array $attributes): bool
    {
        if ($attributes === []) {
            return true;
        }

        try {
            $response = $this->client->put('/v3/contacts/'.rawurlencode($whatsapp), [
                'query' => ['identifierType' => 'whatsapp_id'],
                'json' => ['attributes' => $attributes],
            ]);

            return $response->getStatusCode() === 204;
        } catch (ClientException $e) {
            // Brevo: {"code":"document_not_found","message":"Contact does not exist"}
            if ($e->getResponse()->getStatusCode() !== 404) {
                throw new RuntimeException($e->getMessage(), previous: $e);
            }
        } catch (GuzzleException $e) {
            throw new RuntimeException($e->getMessage(), previous: $e);
        }

        return $this->createWhatsappContact($whatsapp, $attributes);
    }

    /**
     * WhatsApp numarasıyla yeni Brevo kişisi oluşturur.
     *
     * Brevo kişi oluştururken e-posta, ext_id veya SMS'ten birini ister; e-posta
     * mağazaya ait olup birden çok numara taşıyabileceğinden kimlik olarak
     * numaranın kendisi ext_id'ye yazılır. updateEnabled ile aynı ext_id'li
     * kişi varsa oluşturmak yerine güncellenir (201 oluşturuldu / 204 güncellendi).
     *
     * @param array<string, mixed> $attributes
     */
    public function createWhatsappContact(string $whatsapp, array $attributes): bool
    {
        try {
            $response = $this->client->post('/v3/contacts', [
                'json' => [
                    'ext_id'        => $whatsapp,
                    'attributes'    => ['WHATSAPP' => $whatsapp] + $attributes,
                    'updateEnabled' => true,
                ],
            ]);

            return in_array($response->getStatusCode(), [201, 204], true);
        } catch (GuzzleException $e) {
            throw new RuntimeException($e->getMessage(), previous: $e);
        }
    }

    private function recipients(string $toName, array $emails): array
    {
        return array_map(function ($email) use ($toName) {
            return ['email' => $email, 'name' => $toName];
        }, $emails);
    }
}

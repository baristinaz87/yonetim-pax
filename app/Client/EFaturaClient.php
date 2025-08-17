<?php

namespace App\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class EFaturaClient
{
    private Client $client;

    public function __construct()
    {
        $token = env('EFATURA_TOKEN');
        $url = env('EFATURA_URL');
        $this->client = new Client([
            "base_uri" => $url,
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function getMerchant(int $id): array
    {
        try {
            $response = $this->client->get('/api/merchant/id/'. $id);
            $content = $response->getBody()->getContents();
            return json_decode($content, true);
        } catch (GuzzleException $e) {
            //TODO
            dd($e->getMessage());
        }
    }

    public function updateMerchant(int $id, $values): array
    {
        try {
            $response = $this->client->put('/api/merchant/id/'. $id, ["json" => $values]);
            $content = $response->getBody()->getContents();
            return json_decode($content, true);
        } catch (GuzzleException $e) {
            //TODO
            dd($e->getMessage());
        }
    }

    public function getMerchants(int $page, int $perPage, ?string $sortField, string $sortDirection, array $search = []): array
    {
        try {
            $query = ["page" => $page, "per_page" => $perPage, "include" => "setting"];
            if (!empty($search)) {
                foreach ($search as $key => $value) {
                    $query["filter[{$key}]"] = $value;
                }
            }
            if (!empty($sortField)) {
                $query["sort"] = ($sortDirection === "asc" ? "" : "-").($sortField);
            }
            $response = $this->client->get('/api/merchants?'.http_build_query($query));
            $content = $response->getBody()->getContents();
            return json_decode($content, true);
        } catch (GuzzleException $e) {
            //TODO
            dd($e->getMessage());
        }
    }

    public function getMerchantCharges(string $id, int $page, int $perPage): array
    {
        try {
            $query = ["page" => $page, "per_page" => $perPage, "sort" => "-created_at"];
            $response = $this->client->get('/api/merchant/'.$id.'/charges?'.http_build_query($query));
            $content = $response->getBody()->getContents();
            return json_decode($content, true);
        } catch (GuzzleException $e) {
            //TODO
            dd($e->getMessage());
        }
    }

    public function getMerchantNotes(string $id, int $page, int $perPage): array
    {
        try {
            $query = ["page" => $page, "per_page" => $perPage, "sort" => "-created_at"];
            $response = $this->client->get('/api/merchant/'.$id.'/notes?'.http_build_query($query));
            $content = $response->getBody()->getContents();
            return json_decode($content, true);
        } catch (GuzzleException $e) {
            //TODO
            dd($e->getMessage());
        }
    }

    public function createMerchantNote($values): array
    {
        try {
            $response = $this->client->post('/api/user-notes', ["json" => $values]);
            $content = $response->getBody()->getContents();
            return json_decode($content, true);
        } catch (GuzzleException $e) {
            //TODO
            dd($e->getMessage());
        }
    }

    public function removeMerchantNote(string $id): array
    {
        try {
            $response = $this->client->delete('/api/user-notes/'. $id);
            $content = $response->getBody()->getContents();
            return json_decode($content, true);
        } catch (GuzzleException $e) {
            //TODO
            dd($e->getMessage());
        }
    }

    public function getMerchantStatusReport(): array
    {
        try {
            $response = $this->client->get('/api/merchants/status-report');
            $content = $response->getBody()->getContents();
            return json_decode($content, true);
        } catch (GuzzleException $e) {
            //TODO
            dd($e->getMessage());
        }
    }
}

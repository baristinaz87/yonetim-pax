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
}

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

    public function getMerchantByDomain(string $domain): array
    {
        try {
            $response = $this->client->get('/api/merchant/domain/'. $domain);
            $content = $response->getBody()->getContents();
            return json_decode($content, true);
        } catch (GuzzleException $e) {
            //TODO
            dd($e->getMessage());
        }
    }

    public function addCredit($values): array
    {
        try {
            $response = $this->client->post('/api/merchant/createCharge', ["json" => $values]);
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

    /**
     * VKN'ye (tax_number) göre gruplanmış, sadece birden fazla mağazaya
     * sahip kullanıcıları döner. Her grup, o VKN'ye bağlı tüm mağazaların
     * listesini ve toplam mağaza sayısını içerir.
     *
     * @return array{
     *     multi_store_count: int,
     *     groups: array<int, array{
     *         vkn: string,
     *         count: int,
     *         merchants: array<int, array{merchant_id: int, domain: string, unvan: string}>
     *     }>
     * }
     */
    public function getMultiStoreGroups(): array
    {
        try {
            // Sayfa başına tüm kayıtları çek
            $response = $this->client->get('/api/merchants?' . http_build_query([
                "page"     => 1,
                "per_page" => 100000,
                "include"  => "setting",
            ]));
            $content = $response->getBody()->getContents();
            $payload = json_decode($content, true);
            $merchants = $payload["data"] ?? [];

            $groups = [];
            foreach ($merchants as $item) {
                $vkn = isset($item["setting"]["tax_number"])
                    ? trim((string) $item["setting"]["tax_number"])
                    : "";
                if ($vkn === "") {
                    continue;
                }
                if (!isset($groups[$vkn])) {
                    $groups[$vkn] = [
                        "vkn"       => $vkn,
                        "count"     => 0,
                        "merchants" => [],
                    ];
                }
                $groups[$vkn]["count"]++;
                $groups[$vkn]["merchants"][] = [
                    "merchant_id" => $item["id"] ?? null,
                    "domain"      => trim((string) ($item["setting"]["shop_domain"] ?? "")),
                    "unvan"       => trim((string) ($item["setting"]["unvan"] ?? "")),
                ];
            }

            // Sadece 1'den fazla mağazası olan gruplar
            $multi = array_values(array_filter(
                $groups,
                fn($g) => $g["count"] > 1
            ));

            // En çok mağaza en üstte
            usort($multi, fn($a, $b) => $b["count"] <=> $a["count"]);

            return [
                "multi_store_count" => count($multi),
                "groups"            => $multi,
            ];
        } catch (GuzzleException $e) {
            //TODO
            dd($e->getMessage());
        }
    }

    public function getMerchantOtherInvoices(
        string $id,
        int $page,
        int $perPage,
        ?string $source = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        try {
            $query = [
                "page" => $page,
                "per_page" => $perPage,
                "sort" => "-execution_date",
            ];

            if (!empty($source)) {
                $query["filter[source]"] = $source;
            }
            if (!empty($startDate)) {
                $query["filter[execution_date_from]"] = $startDate;
            }
            if (!empty($endDate)) {
                $query["filter[execution_date_to]"] = $endDate;
            }

            $response = $this->client->get('/api/merchant/'.$id.'/other-invoices?'.http_build_query($query));
            $content = $response->getBody()->getContents();
            return json_decode($content, true);
        } catch (GuzzleException $e) {
            //TODO
            dd($e->getMessage());
        }
    }
}

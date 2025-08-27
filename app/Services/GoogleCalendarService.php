<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Carbon\Carbon;

class GoogleCalendarService
{
    protected $client;
    protected $calendarService;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect'));
        $this->client->setScopes(config('services.google.calendar.scopes'));
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        $this->calendarService = new Calendar($this->client);
    }

    /**
     * Get Google OAuth authorization URL
     */
    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Handle OAuth callback and get access token
     */
    public function handleCallback($code)
    {
        $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);

        if (isset($accessToken['error'])) {
            throw new \Exception('Error fetching access token: ' . $accessToken['error']);
        }

        return $accessToken;
    }

    /**
     * Create calendar event for credit reminder
     */
    public function createCreditReminderEvent($shopName, $creditExpiredAt, $accessToken)
    {
        $this->client->setAccessToken($accessToken);

        // Refresh token if expired
        if ($this->client->isAccessTokenExpired()) {
            $this->client->refreshToken($this->client->getRefreshToken());
        }

        // Create event 2 days before expiration
        $eventDate = Carbon::parse($creditExpiredAt)->subDays(2);
        $eventStart = $eventDate->setTime(9, 0, 0); // 09:00
        $eventEnd = $eventDate->setTime(10, 0, 0); // 10:00

        $event = new Event();
        $event->setSummary("{$shopName} kontörü bitiyor");
        $event->setDescription("{$shopName} mağazasının kontörü yakında bitecek. Lütfen kontör yükleme işlemini gerçekleştirin.");

        $start = new EventDateTime();
        $start->setDateTime($eventStart->toISOString());
        $start->setTimeZone('Europe/Istanbul');
        $event->setStart($start);

        $end = new EventDateTime();
        $end->setDateTime($eventEnd->toISOString());
        $end->setTimeZone('Europe/Istanbul');
        $event->setEnd($end);

        try {
            $createdEvent = $this->calendarService->events->insert('primary', $event);
            return $createdEvent;
        } catch (\Exception $e) {
            throw new \Exception('Error creating calendar event: ' . $e->getMessage());
        }
    }

    /**
     * Get calendar service instance
     */
    public function getCalendarService()
    {
        return $this->calendarService;
    }

    /**
     * Get client instance
     */
    public function getClient()
    {
        return $this->client;
    }
}

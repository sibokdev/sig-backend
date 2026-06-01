<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsappService
{
    private string $base;

    public function __construct()
    {
        $this->base = rtrim(env('WHATSAPP_SERVICE_URL', 'http://localhost:3001'), '/');
    }

    /**
     * Return status including all sessions.
     * { connected, connectedCount, sessions: [{id, connected, hasQr}] }
     */
    public function status(): array
    {
        try {
            $res = Http::timeout(3)->get("{$this->base}/status");
            return $res->ok() ? $res->json() : ['connected' => false, 'connectedCount' => 0, 'sessions' => []];
        } catch (\Throwable) {
            return ['connected' => false, 'connectedCount' => 0, 'sessions' => []];
        }
    }

    /** Legacy compat: returns true if at least one session is connected */
    public function isConnected(): bool
    {
        return (bool) ($this->status()['connected'] ?? false);
    }

    /**
     * Create or reconnect a session by ID.
     * Returns { id, connected, qrUrl }
     */
    public function createSession(string $id): array
    {
        $res = Http::timeout(10)->post("{$this->base}/sessions", ['id' => $id]);
        if ($res->failed()) {
            throw new \RuntimeException($res->json()['error'] ?? "Session create error {$res->status()}");
        }
        return $res->json();
    }

    /**
     * Remove a session by ID.
     */
    public function removeSession(string $id): void
    {
        Http::timeout(5)->delete("{$this->base}/sessions/{$id}");
    }

    /**
     * Send a text message (auto load-balanced across all connected sessions).
     * @param string $phone Full number with country code, no + (e.g. "521234567890")
     */
    public function send(string $phone, string $message): array
    {
        $res = Http::timeout(15)->post("{$this->base}/send", [
            'phone'   => $phone,
            'message' => $message,
        ]);

        if ($res->failed()) {
            throw new \RuntimeException($res->json()['error'] ?? "WhatsApp service error {$res->status()}");
        }

        return $res->json();
    }

    /**
     * Send via a specific session (bypass load balancer).
     */
    public function sendVia(string $sessionId, string $phone, string $message): array
    {
        $res = Http::timeout(15)->post("{$this->base}/send/{$sessionId}", [
            'phone'   => $phone,
            'message' => $message,
        ]);

        if ($res->failed()) {
            throw new \RuntimeException($res->json()['error'] ?? "WhatsApp send error {$res->status()}");
        }

        return $res->json();
    }

    /**
     * Send a message with an image URL (load-balanced).
     */
    public function sendImage(string $phone, string $imageUrl, string $caption = ''): array
    {
        $res = Http::timeout(30)->post("{$this->base}/send-image", [
            'phone'    => $phone,
            'imageUrl' => $imageUrl,
            'message'  => $caption,
        ]);

        if ($res->failed()) {
            throw new \RuntimeException($res->json()['error'] ?? "WhatsApp image send error {$res->status()}");
        }

        return $res->json();
    }
}

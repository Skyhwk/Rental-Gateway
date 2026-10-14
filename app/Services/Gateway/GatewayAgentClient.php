<?php

namespace App\Services\Gateway;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GatewayAgentClient
{
    public function isEnabled()
    {
        return (bool) config('gateway.agent.enabled') && config('gateway.agent.token');
    }

    /**
     * @return array<string, mixed>
     */
    public function apply(array $payload, $idempotencyKey)
    {
        $baseUrl = rtrim(config('gateway.agent.base_url'), '/');
        $timeout = (int) config('gateway.agent.timeout', 30);

        $response = Http::timeout($timeout)
            ->withToken(config('gateway.agent.token'))
            ->withHeaders([
                'X-Request-Id' => (string) Str::uuid(),
                'X-Idempotency-Key' => $idempotencyKey,
                'Accept' => 'application/json',
            ])
            ->post($baseUrl.'/v1/apply', $payload);

        if ($response->status() === 409) {
            return [
                'status' => 'busy',
                'retry_after' => (int) $response->header('Retry-After', 5),
            ];
        }

        if ($response->successful()) {
            return $response->json();
        }

        $body = $response->json();

        return is_array($body)
            ? $body
            : ['status' => 'failed', 'error' => $response->body()];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function remove($applicationId)
    {
        $baseUrl = rtrim(config('gateway.agent.base_url'), '/');
        $timeout = (int) config('gateway.agent.timeout', 30);

        $response = Http::timeout($timeout)
            ->withToken(config('gateway.agent.token'))
            ->withHeaders(['Accept' => 'application/json'])
            ->post($baseUrl.'/v1/remove', ['application_id' => (string) $applicationId]);

        if ($response->successful()) {
            return $response->json();
        }

        $body = $response->json();

        throw new \RuntimeException(is_array($body) ? ($body['error'] ?? 'remove failed') : $response->body());
    }

    /**
     * @return array<string, mixed>
     */
    public function health()
    {
        if (! $this->isEnabled()) {
            return ['status' => 'disabled'];
        }

        try {
            $baseUrl = rtrim(config('gateway.agent.base_url'), '/');

            return Http::timeout(5)
                ->withToken(config('gateway.agent.token'))
                ->get($baseUrl.'/v1/health')
                ->throw()
                ->json();
        } catch (RequestException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}

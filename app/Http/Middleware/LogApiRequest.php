<?php

namespace App\Http\Middleware;

use App\Models\ApiRequest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    /**
     * Headers sensíveis que não devem ser logados.
     *
     * @var array<string>
     */
    protected array $sensitiveHeaders = [
        'authorization',
        'cookie',
        'x-csrf-token',
        'x-xsrf-token',
    ];

    /**
     * Campos sensíveis que devem ser mascarados no body.
     *
     * @var array<string>
     */
    protected array $sensitiveFields = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'secret',
        'api_key',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $this->logRequest($request, $response, $startTime);

        return $response;
    }

    /**
     * Log the API request.
     */
    protected function logRequest(Request $request, Response $response, float $startTime): void
    {
        $user = $request->user();
        $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

        // Obter token ID se disponível
        $tokenId = null;
        $deviceName = null;
        if ($user) {
            /** @var \Laravel\Sanctum\PersonalAccessToken|null $token */
            $token = $user->currentAccessToken();
            $tokenId = $token?->id;
            $deviceName = $token?->name;
        }

        // Preparar response body (limitar tamanho)
        $responseBody = null;
        $responseContent = $response->getContent();
        if ($responseContent && strlen($responseContent) < 10000) {
            $decoded = json_decode($responseContent, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $responseBody = $this->maskSensitiveData($decoded);
            }
        }

        ApiRequest::create([
            'user_id' => $user?->id,
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
            'full_url' => $request->fullUrl(),
            'route_name' => $request->route()?->getName(),
            'ip_address' => $request->ip() ?? 'unknown',
            'user_agent' => $request->userAgent(),
            'headers' => $this->filterHeaders($request->headers->all()),
            'query_params' => $request->query() ?: null,
            'request_body' => $this->maskSensitiveData($request->except($this->sensitiveFields)),
            'status_code' => $response->getStatusCode(),
            'response_body' => $responseBody,
            'response_time_ms' => $responseTimeMs,
            'device_name' => $deviceName,
            'token_id' => $tokenId,
        ]);
    }

    /**
     * Filter out sensitive headers.
     *
     * @param  array<string, array<string>>  $headers
     * @return array<string, string>
     */
    protected function filterHeaders(array $headers): array
    {
        $filtered = [];

        foreach ($headers as $key => $values) {
            $lowerKey = strtolower($key);
            if (in_array($lowerKey, $this->sensitiveHeaders)) {
                $filtered[$key] = '[REDACTED]';
            } else {
                $filtered[$key] = is_array($values) ? implode(', ', $values) : $values;
            }
        }

        return $filtered;
    }

    /**
     * Mask sensitive data in arrays.
     *
     * @param  mixed  $data
     * @return mixed
     */
    protected function maskSensitiveData(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $this->sensitiveFields)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            }
        }

        return $data;
    }
}

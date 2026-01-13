<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Runtimes\Auth;

use ArtisanBuild\Pusher\Auth\AuthTransport;
use ArtisanBuild\Pusher\Auth\Options\AuthRequestType;
use ArtisanBuild\Pusher\Auth\Options\ChannelAuthorizationData;
use ArtisanBuild\Pusher\Auth\Options\InternalAuthOptions;
use ArtisanBuild\Pusher\Auth\Options\UserAuthenticationData;
use ArtisanBuild\Pusher\Errors\HTTPAuthError;
use ArtisanBuild\Pusher\Runtimes\Runtime;
use Exception;

class HttpAuth implements AuthTransport
{
    public function __invoke(
        Runtime $context,
        string $query,
        InternalAuthOptions $authOptions,
        AuthRequestType $authRequestType,
        callable $callback,
    ): void {
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        // Merge static headers
        foreach ($authOptions->headers as $key => $value) {
            $headers[$key] = $value;
        }

        // Merge dynamic headers from provider
        if ($authOptions->headersProvider !== null) {
            $dynamicHeaders = ($authOptions->headersProvider)();
            foreach ($dynamicHeaders as $key => $value) {
                $headers[$key] = $value;
            }
        }

        // Build HTTP header string
        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= "{$key}: {$value}\r\n";
        }

        // Use shorter timeout for testing, longer for production
        $timeout = (int) (getenv('PUSHER_AUTH_TIMEOUT') ?: 5);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => $headerString,
                'content' => $query,
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ]);

        // Check if endpoint looks like a valid URL (has scheme)
        if (! str_starts_with($authOptions->endpoint, 'http://') &&
            ! str_starts_with($authOptions->endpoint, 'https://')) {
            $error = new HTTPAuthError(
                0,
                "Invalid auth endpoint: {$authOptions->endpoint}. ".
                'Must be a full URL (http:// or https://).'
            );
            $callback($error, $this->createEmptyData($authRequestType));

            return;
        }

        $response = @file_get_contents($authOptions->endpoint, false, $context);

        // Get HTTP response code from headers
        $statusCode = 0;
        $responseHeaders = http_get_last_response_headers() ?? [];
        foreach ($responseHeaders as $header) {
            if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                $statusCode = (int) $matches[1];
                break;
            }
        }

        if ($response === false || $statusCode !== 200) {
            $error = new HTTPAuthError(
                $statusCode,
                'Unable to retrieve auth string from channel-authorization endpoint - '.
                "received status: {$statusCode} from {$authOptions->endpoint}. ".
                'Clients must be authorized to join private or presence channels.'
            );
            $callback($error, $this->createEmptyData($authRequestType));

            return;
        }

        $data = json_decode($response, true);

        if ($data === null) {
            $error = new Exception('Invalid JSON response from auth endpoint');
            $callback($error, $this->createEmptyData($authRequestType));

            return;
        }

        if ($authRequestType === AuthRequestType::ChannelAuthorization) {
            $callback(null, new ChannelAuthorizationData(
                auth: $data['auth'] ?? '',
                channel_data: $data['channel_data'] ?? null,
                shared_secret: $data['shared_secret'] ?? null,
            ));
        } else {
            $callback(null, new UserAuthenticationData(
                auth: $data['auth'] ?? '',
                user_data: $data['user_data'] ?? null,
            ));
        }
    }

    private function createEmptyData(AuthRequestType $type): ChannelAuthorizationData|UserAuthenticationData
    {
        if ($type === AuthRequestType::ChannelAuthorization) {
            return new ChannelAuthorizationData(auth: '');
        }

        return new UserAuthenticationData(auth: '');
    }
}

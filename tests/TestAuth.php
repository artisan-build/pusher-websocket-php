<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Tests;

use ArtisanBuild\Pusher\Auth\AuthTransport;
use ArtisanBuild\Pusher\Auth\Options\AuthRequestType;
use ArtisanBuild\Pusher\Auth\Options\ChannelAuthorizationData;
use ArtisanBuild\Pusher\Auth\Options\InternalAuthOptions;
use ArtisanBuild\Pusher\Auth\Options\UserAuthenticationData;
use ArtisanBuild\Pusher\Errors\HTTPAuthError;
use ArtisanBuild\Pusher\Runtimes\Runtime;

/**
 * Test auth transport that generates valid signatures without HTTP.
 *
 * This transport reads PUSHER_APP_KEY and PUSHER_APP_SECRET from the
 * environment and generates valid Pusher auth signatures directly.
 * No mock server needed.
 *
 * Usage in tests:
 *   $options->channelAuthorization = [
 *       'transport' => 'test',
 *   ];
 *
 * To simulate auth failure, include 'unauthorized' in the channel name:
 *   $pusher->subscribe('private-unauthorized-test');
 */
class TestAuth implements AuthTransport
{
    public function __invoke(
        Runtime $context,
        string $query,
        InternalAuthOptions $authOptions,
        AuthRequestType $authRequestType,
        callable $callback,
    ): void {
        // Parse the query string to get socket_id and channel_name
        parse_str($query, $params);
        $socketId = $params['socket_id'] ?? '';
        $channelName = $params['channel_name'] ?? '';

        // Simulate auth failure for channels containing 'unauthorized'
        if (str_contains($channelName, 'unauthorized')) {
            $error = new HTTPAuthError(403, 'Forbidden');
            $callback($error, new ChannelAuthorizationData(auth: ''));

            return;
        }

        $appKey = getenv('PUSHER_APP_KEY') ?: 'app-key';
        $appSecret = getenv('PUSHER_APP_SECRET') ?: 'app-secret';

        $isPresence = str_starts_with($channelName, 'presence-');

        if ($isPresence) {
            // Presence channels require channel_data with user_id
            $userId = 'test_user_' . substr(md5($socketId), 0, 8);
            $channelData = json_encode([
                'user_id' => $userId,
                'user_info' => [
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                ],
            ]);

            // String to sign: socket_id:channel_name:channel_data
            $stringToSign = "{$socketId}:{$channelName}:{$channelData}";
            $signature = hash_hmac('sha256', $stringToSign, $appSecret);

            $callback(null, new ChannelAuthorizationData(
                auth: "{$appKey}:{$signature}",
                channel_data: $channelData,
            ));
        } else {
            // Private channels
            // String to sign: socket_id:channel_name
            $stringToSign = "{$socketId}:{$channelName}";
            $signature = hash_hmac('sha256', $stringToSign, $appSecret);

            if ($authRequestType === AuthRequestType::ChannelAuthorization) {
                $callback(null, new ChannelAuthorizationData(
                    auth: "{$appKey}:{$signature}",
                ));
            } else {
                $callback(null, new UserAuthenticationData(
                    auth: "{$appKey}:{$signature}",
                    user_data: json_encode(['id' => 'test_user']),
                ));
            }
        }
    }
}

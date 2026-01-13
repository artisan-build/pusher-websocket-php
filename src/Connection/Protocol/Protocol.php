<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Connection\Protocol;

use ArtisanBuild\Pusher\Connection\Protocol\MessageTypes\PusherEvent;

/**
 * Provides functions for handling Pusher protocol-specific messages.
 */
class Protocol
{
    /**
     * Decodes a message in a Pusher format.
     *
     * The message should contain a pusher event serialized as JSON.
     *
     * Throws errors when messages are not parseable.
     */
    public static function decodeMessage(string|array $messageEvent): PusherEvent
    {
        try {
            // Handle string messages (from WebSocket) or array messages (from other sources)
            if (is_string($messageEvent)) {
                $messageData = json_decode($messageEvent, true);
            } else {
                $messageData = json_decode($messageEvent['data'], true);
            }

            $pusherEventData = $messageData['data'] ?? null;

            if (is_string($pusherEventData)) {
                $decoded = json_decode($pusherEventData, true);
                if ($decoded !== null) {
                    $pusherEventData = $decoded;
                }
            }

            return new PusherEvent(
                event: $messageData['event'],
                channel: $messageData['channel'] ?? null,
                data: $pusherEventData,
                user_id: $messageData['user_id'] ?? null,
            );
        } catch (\Throwable $e) {
            throw new \RuntimeException(json_encode([
                'type' => 'MessageParseError',
                'error' => $e->getMessage(),
                'data' => is_string($messageEvent) ? $messageEvent : ($messageEvent['data'] ?? null),
            ]));
        }
    }

    /**
     * Encodes a message to be sent.
     */
    public static function encodeMessage(PusherEvent $event): string
    {
        return json_encode([
            'event' => $event->event,
            'channel' => $event->channel,
            'data' => $event->data,
        ]);
    }

    /**
     * Processes a handshake message and returns appropriate actions.
     *
     * Returns an Action with action-specific properties.
     */
    public static function processHandshake(string|array $messageEvent): Action
    {
        $message = self::decodeMessage($messageEvent);

        if ($message->event === 'pusher:connection_established') {
            if (! isset($message->data['activity_timeout'])) {
                throw new \RuntimeException('No activity timeout specified in handshake');
            }

            return new Action(
                action: 'connected',
                id: $message->data['socket_id'],
                activityTimeout: $message->data['activity_timeout'] * 1000
            );
        } elseif ($message->event === 'pusher:error') {
            return new Action(
                action: self::getCloseAction($message->data),
                error: self::getCloseError($message->data)
            );
        }

        throw new \RuntimeException('Invalid handshake');
    }

    /**
     * Dispatches the close event and returns an appropriate action name.
     */
    public static function getCloseAction(array $closeEvent): ?string
    {
        $code = $closeEvent['code'] ?? 0;

        if ($code < 4000) {
            if ($code >= 1002 && $code <= 1004) {
                return 'backoff';
            }

            return null;
        } elseif ($code === 4000) {
            return 'tls_only';
        } elseif ($code < 4100) {
            return 'refused';
        } elseif ($code < 4200) {
            return 'backoff';
        } elseif ($code < 4300) {
            return 'retry';
        }

        return 'refused';
    }

    /**
     * Returns an error or null based on the close event.
     */
    public static function getCloseError(array $closeEvent): ?array
    {
        $code = $closeEvent['code'] ?? 0;

        if ($code !== 1000 && $code !== 1001) {
            return [
                'type' => 'PusherError',
                'data' => [
                    'code' => $code,
                    'message' => $closeEvent['reason'] ?? $closeEvent['message'] ?? null,
                ],
            ];
        }

        return null;
    }
}

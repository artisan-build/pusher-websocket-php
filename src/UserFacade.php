<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher;

use ArtisanBuild\Pusher\Auth\Options\UserAuthenticationData;
use ArtisanBuild\Pusher\Channels\Channel;
use ArtisanBuild\Pusher\Events\Dispatcher;
use ArtisanBuild\Pusher\Utils\FlatPromise;
use Closure;

class UserFacade extends Dispatcher
{
    public Pusher $pusher;

    public bool $signin_requested = false;

    public mixed $user_data = null;

    public ?Channel $serverToUserChannel = null;

    public ?FlatPromise $signinDonePromise = null;

    public WatchlistFacade $watchlist;

    private ?Closure $_signinDoneResolve = null;

    public function __construct(Pusher $pusher)
    {
        parent::__construct(function ($eventName, $data) {
            Logger::debug('No callbacks on user for '.$eventName);
        });

        $this->pusher = $pusher;
        $this->pusher->connection->bind('state_change', function ($event) {
            $previous = $event['previous'] ?? null;
            $current = $event['current'] ?? null;

            if ($previous !== 'connected' && $current === 'connected') {
                $this->_signin();
            }
            if ($previous === 'connected' && $current !== 'connected') {
                $this->_cleanup();
                $this->_newSigninPromiseIfNeeded();
            }
        });

        $this->watchlist = new WatchlistFacade($pusher);

        $this->pusher->connection->bind('message', function ($event) {
            $eventName = $event->event ?? null;
            if ($eventName === 'pusher:signin_success') {
                $this->_onSigninSuccess($event->data ?? null);
            }
            if (
                $this->serverToUserChannel &&
                $this->serverToUserChannel->name === ($event->channel ?? null)
            ) {
                $this->serverToUserChannel->handleEvent($event);
            }
        });
    }

    public function signin(): void
    {
        if ($this->signin_requested) {
            return;
        }

        $this->signin_requested = true;
        $this->_signin();
    }

    private function _signin(): void
    {
        if (! $this->signin_requested) {
            return;
        }

        $this->_newSigninPromiseIfNeeded();

        if ($this->pusher->connection->state !== 'connected') {
            return;
        }

        ($this->pusher->config->userAuthenticator)(
            ['socketId' => $this->pusher->connection->socket_id],
            $this->_onAuthorize(...)
        );
    }

    private function _onAuthorize(?object $err, ?UserAuthenticationData $authData): void
    {
        if ($err) {
            Logger::warn('Error during signin: '.$err->getMessage());
            $this->_cleanup();

            return;
        }

        $this->pusher->sendEvent('pusher:signin', [
            'auth' => $authData->auth,
            'user_data' => $authData->user_data,
        ]);
    }

    private function _onSigninSuccess(mixed $data): void
    {
        try {
            $this->user_data = json_decode($data['user_data'] ?? '{}', true);
        } catch (\Throwable $e) {
            Logger::error('Failed parsing user data after signin: '.($data['user_data'] ?? ''));
            $this->_cleanup();

            return;
        }

        if (! isset($this->user_data['id']) || $this->user_data['id'] === '') {
            Logger::error('user_data doesn\'t contain an id. user_data: '.json_encode($this->user_data));
            $this->_cleanup();

            return;
        }

        // Signin succeeded
        if ($this->_signinDoneResolve) {
            ($this->_signinDoneResolve)(true);
        }
        $this->_subscribeChannels();
    }

    private function _subscribeChannels(): void
    {
        $ensure_subscribed = function ($channel) {
            if ($channel->subscriptionPending && $channel->subscriptionCancelled) {
                $channel->reinstateSubscription();
            } elseif (
                ! $channel->subscriptionPending &&
                $this->pusher->connection->state === 'connected'
            ) {
                $channel->subscribe();
            }
        };

        $this->serverToUserChannel = new Channel(
            '#server-to-user-'.$this->user_data['id'],
            $this->pusher
        );
        $this->serverToUserChannel->bindGlobal(function ($eventName, $data) {
            if (
                str_starts_with($eventName, 'pusher_internal:') ||
                str_starts_with($eventName, 'pusher:')
            ) {
                return;
            }
            $this->emit($eventName, $data);
        });
        $ensure_subscribed($this->serverToUserChannel);
    }

    private function _cleanup(): void
    {
        $this->user_data = null;
        if ($this->serverToUserChannel) {
            $this->serverToUserChannel->unbindAll();
            $this->serverToUserChannel->disconnect();
            $this->serverToUserChannel = null;
        }

        if ($this->signin_requested && $this->_signinDoneResolve) {
            ($this->_signinDoneResolve)(true);
        }
    }

    private function _newSigninPromiseIfNeeded(): void
    {
        if (! $this->signin_requested) {
            return;
        }

        if ($this->signinDonePromise && $this->signinDonePromise->resolved) {
            return;
        }

        $result = FlatPromise::create();
        $this->signinDonePromise = $result['promise'];
        $this->_signinDoneResolve = $result['resolve'];
    }
}

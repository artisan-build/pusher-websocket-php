<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth\Options;

enum AuthRequestType: string
{
    case UserAuthentication = 'user-authentication';
    case ChannelAuthorization = 'channel-authorization';
}

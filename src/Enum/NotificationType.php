<?php

declare(strict_types=1);

namespace App\Enum;

enum NotificationType: string
{
    case SERVER_INVITATION          = 'server_invitation';
    case INVITATION_ACCEPTED        = 'invitation_accepted';
    case KICKED_FROM_SERVER         = 'kicked_from_server';
    case BANNED_FROM_SERVER         = 'banned_from_server';
    case FRIEND_INVITATION          = 'friend_invitation';
    case FRIEND_INVITATION_ACCEPTED = 'friend_invitation_accepted';
    case SERVER_DELETED             = 'server_deleted';

}
